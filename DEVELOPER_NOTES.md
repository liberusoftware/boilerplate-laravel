# Developer Notes - Private Messaging System

## Overview

This document contains important notes for developers working with or extending the private messaging system.

## Implementation Constraints

### PHP Version Requirement
- This project requires **PHP 8.4+** as specified in `composer.json`
- The messaging system was developed with PHP 8.3.6 available in the testing environment
- All code is compatible with PHP 8.3+ but deployment requires PHP 8.4+

### Testing Status
- ✅ All PHP files pass syntax validation
- ✅ 24 comprehensive test cases written
- ⚠️ Tests not executed due to PHP version requirement in environment
- ✅ Code review completed with all issues resolved
- ✅ CodeQL security scan passed

## Key Design Decisions

### 1. Message Encryption
**Decision:** Encrypt message bodies at rest using Laravel's `Crypt` facade

**Rationale:**
- Provides strong AES-256-CBC encryption
- Built into Laravel, no additional dependencies
- Automatic key management via APP_KEY
- Easy to decrypt when needed for display

**Implementation:**
```php
// Encryption (on save)
$encryptedBody = Crypt::encryptString($messageBody);

// Decryption (on retrieval)
$decryptedBody = Crypt::decryptString($message->body);
```

**Trade-offs:**
- ✅ Strong security
- ✅ No additional infrastructure needed
- ❌ Cannot search encrypted messages without decrypting
- ❌ Key rotation requires re-encrypting all messages

### 2. Authorization Strategy
**Decision:** Use Laravel Policies for message access control

**Rationale:**
- Standard Laravel pattern
- Centralized authorization logic
- Easy to test and maintain
- Works with Blade directives and middleware

**Rules:**
- Users can view messages they sent OR received
- Users can update messages they sent
- Users can delete messages they sent OR received

### 3. API vs Web Routes
**Decision:** Separate API endpoints (Sanctum auth) from web routes (session auth)

**Rationale:**
- Follows Laravel best practices
- Enables both web UI and external API access
- Different authentication methods for different use cases
- Allows for future mobile app integration

### 4. Frontend Framework
**Decision:** Use Alpine.js instead of Vue.js or React

**Rationale:**
- Lightweight (no build step required for MVP)
- Integrates well with Blade templates
- Sufficient for messaging UI requirements
- Consistent with Livewire ecosystem

**Future Consideration:** Could migrate to Livewire components for better Laravel integration

## Database Design Notes

### Indexes
Two indexes were added for performance:

1. **Composite index on (sender_id, recipient_id)**
   - Optimizes conversation queries
   - Speeds up the `between()` scope

2. **Single index on recipient_id**
   - Optimizes unread message queries
   - Used frequently in the index view

### Why No Conversation Table?
**Decision:** Messages stored directly without a separate conversations table

**Rationale:**
- Simpler schema
- Fewer joins required
- Direct message queries are fast
- Conversations derived dynamically

**Trade-off:** If adding group messaging, would need to refactor to add conversations table

## Security Considerations

### 1. Why Encrypt Individual Messages?
Instead of encrypting entire columns, each message body is encrypted separately:
- Allows for selective decryption
- Each message can have different encryption if needed
- Follows principle of least privilege

### 2. Authorization at Multiple Layers
Authorization is checked in:
1. **Routes:** Middleware ensures authentication
2. **Controller:** Policy checks before operations
3. **Views:** Blade directives for UI elements

This defense-in-depth approach prevents bypassing authorization.

### 3. XSS Prevention
Using `@json()` directive instead of raw `{{ }}` in JavaScript contexts:
```blade
// ✅ Safe
currentUserId: @json(Auth::id())

// ❌ Potentially unsafe
currentUserId: {{ Auth::id() }}
```

### 4. CSRF Protection
All POST/PATCH/DELETE requests include CSRF token:
```javascript
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

## Performance Considerations

### Current Implementation
- ✅ Database queries are optimized with proper indexes
- ✅ Eager loading used with `->with(['sender', 'recipient'])`
- ✅ Messages retrieved in batches, not individually

### Potential Optimizations
1. **Pagination:** Large conversation histories should be paginated
2. **Caching:** Unread counts could be cached
3. **Broadcasting:** Use Laravel Echo for real-time updates
4. **Queue Jobs:** Send email notifications via queue

## Extension Points

### 1. Adding Real-time Features
To add WebSocket support:
1. Install Laravel Echo and Pusher/Socket.io
2. Create `MessageSent` event
3. Broadcast on message creation
4. Listen in frontend with Echo

### 2. Adding File Attachments
To support file sharing:
1. Add `attachments` table with polymorphic relation
2. Use Laravel's file storage system
3. Add file upload endpoints
4. Update UI to show attachments

### 3. Adding Group Messaging
To support group conversations:
1. Create `conversations` table
2. Create `conversation_participants` pivot table
3. Refactor Message to belong to Conversation
4. Update UI for group member management

### 4. Adding Message Search
To add search functionality:
1. Consider using Laravel Scout
2. Decrypt messages for indexing (security trade-off)
3. Or implement client-side search after loading
4. Add search UI to conversation view

## Testing Notes

### Test Structure
Tests are organized by concern:
- **MessageTest.php:** Model logic, relationships, scopes
- **MessageApiTest.php:** API endpoints, authorization, validation

### Running Tests
```bash
# All tests
php artisan test

# Only message tests
php artisan test --filter=Message

# With coverage
php artisan test --coverage
```

### Test Database
Tests use `migrate:fresh` in `beforeEach` to ensure clean state. This is acceptable for small test suites but may need optimization for larger suites.

## Common Issues and Solutions

### Issue: Messages Not Decrypting
**Cause:** APP_KEY changed or missing
**Solution:** Ensure APP_KEY in .env matches the key used to encrypt

### Issue: Authorization Failures
**Cause:** Policy not registered
**Solution:** Policies are auto-discovered in Laravel 12, ensure file is in `app/Policies/`

### Issue: CSRF Token Mismatch
**Cause:** Session expired or token not included
**Solution:** Include token in all POST/PATCH/DELETE requests

### Issue: XSS Vulnerability Warnings
**Cause:** Using `{{ }}` in JavaScript context
**Solution:** Use `@json()` directive for safe JSON encoding

## Code Style Guidelines

### Controller Methods
- Keep methods focused on single responsibility
- Extract complex logic to service classes if needed
- Always return JSON for API endpoints
- Include proper HTTP status codes

### Model Scopes
- Use descriptive names (e.g., `scopeBetween`, not `scopeGetBetween`)
- Include PHPDoc with parameter and return types
- Keep scopes simple and composable

### Views
- Separate layout from content
- Use Alpine.js for interactivity
- Keep JavaScript in script tags, not inline
- Use Tailwind utility classes

## Deployment Checklist

Before deploying to production:

1. ✅ Run migrations: `php artisan migrate`
2. ✅ Clear caches: `php artisan config:clear && php artisan cache:clear`
3. ✅ Compile assets: `npm run build`
4. ✅ Set APP_ENV to production
5. ✅ Enable HTTPS for Sanctum cookies
6. ✅ Configure CORS if using separate frontend
7. ✅ Set up queue workers if adding notifications
8. ✅ Configure mail settings for notifications
9. ✅ Review and update rate limiting
10. ✅ Set up monitoring for message delivery

## Maintenance Tasks

### Regular Maintenance
- Monitor database size (messages table can grow large)
- Consider archiving old messages
- Review and rotate encryption keys periodically
- Monitor for failed message deliveries

### Monitoring Metrics
Suggested metrics to track:
- Messages sent per day
- Average response time
- Unread message count per user
- Failed encryption/decryption attempts
- API endpoint response times

## Contributing

When extending this system:
1. Write tests first (TDD approach recommended)
2. Follow existing code style and patterns
3. Update documentation (especially MESSAGING.md)
4. Run code review before committing
5. Ensure backward compatibility
6. Add migration for schema changes

## Questions?

For questions about this implementation:
1. Check MESSAGING.md for API documentation
2. Review ARCHITECTURE.md for system design
3. Check IMPLEMENTATION_SUMMARY.md for feature details
4. Review test files for usage examples

## License

Same as parent project (MIT)
