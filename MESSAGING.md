# Private Messaging System

This Laravel application includes a secure private messaging system that allows users to send direct messages to each other.

## Features

- **Private Messaging**: Users can send and receive private messages to/from other users
- **Message Encryption**: All messages are encrypted in the database using Laravel's encryption
- **Read Receipts**: Track when messages have been read
- **Real-time Conversations**: View message history between users
- **Security**: Authorization policies ensure users can only view their own messages
- **RESTful API**: Complete API endpoints for messaging operations

## Database Schema

### Messages Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| sender_id | bigint | Foreign key to users table |
| recipient_id | bigint | Foreign key to users table |
| body | text | Encrypted message content |
| read_at | timestamp | When message was read (nullable) |
| created_at | timestamp | When message was created |
| updated_at | timestamp | When message was updated |

## API Endpoints

All endpoints require authentication via Laravel Sanctum.

### Get Conversations
```
GET /api/messages
```
Returns list of all conversations for the authenticated user.

**Response:**
```json
[
  {
    "user": {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "last_message": {
      "id": 5,
      "body": "Hello!",
      "created_at": "2026-02-14T12:00:00.000000Z"
    },
    "unread_count": 3
  }
]
```

### Get Conversation with User
```
GET /api/messages/{userId}
```
Returns all messages between authenticated user and specified user.

**Response:**
```json
{
  "messages": [
    {
      "id": 1,
      "sender_id": 1,
      "recipient_id": 2,
      "body": "Hello!",
      "read_at": null,
      "created_at": "2026-02-14T12:00:00.000000Z"
    }
  ],
  "user": {
    "id": 2,
    "name": "John Doe"
  }
}
```

### Send Message
```
POST /api/messages
```

**Request Body:**
```json
{
  "recipient_id": 2,
  "body": "Hello, this is my message"
}
```

**Response:**
```json
{
  "message": {
    "id": 1,
    "sender_id": 1,
    "recipient_id": 2,
    "body": "Hello, this is my message",
    "created_at": "2026-02-14T12:00:00.000000Z"
  },
  "success": true
}
```

### Mark Message as Read
```
PATCH /api/messages/{messageId}/read
```

**Response:**
```json
{
  "success": true,
  "message": {
    "id": 1,
    "read_at": "2026-02-14T12:05:00.000000Z"
  }
}
```

### Delete Message
```
DELETE /api/messages/{messageId}
```

**Response:**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

### Get List of Users
```
GET /api/messages/users
```
Returns list of all users except the authenticated user.

### Get Unread Count
```
GET /api/messages/unread-count
```
Returns count of unread messages for authenticated user.

**Response:**
```json
{
  "count": 5
}
```

## Web Interface

### Message List
```
GET /messages
```
View all conversations with unread counts and last messages.

### Conversation View
```
GET /messages/{userId}
```
View and send messages to a specific user.

## Security Features

1. **Encryption**: All message bodies are encrypted using Laravel's `Crypt` facade before storage
2. **Authorization**: MessagePolicy ensures users can only:
   - View messages they sent or received
   - Update/delete their own sent messages
   - Delete messages they received
3. **Authentication**: All routes require authentication via Sanctum
4. **Validation**: 
   - Users cannot send messages to themselves
   - Recipients must exist in the database
   - Message body is required and limited to 5000 characters

## Usage Examples

### JavaScript (Fetch API)

```javascript
// Send a message
const response = await fetch('/api/messages', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        recipient_id: 2,
        body: 'Hello!'
    })
});

const data = await response.json();
```

### Laravel Backend

```php
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

// Create a message
$recipient = User::find(2);
$message = Message::create([
    'sender_id' => auth()->id(),
    'recipient_id' => $recipient->id,
    'body' => Crypt::encryptString('Hello, this is my message'),
]);

// Get messages between two users
$messages = Message::between(auth()->id(), $recipient->id)
    ->orderBy('created_at', 'asc')
    ->get();

// Mark as read
$message->markAsRead();

// Get unread messages
$unreadMessages = auth()->user()
    ->receivedMessages()
    ->unread()
    ->get();
```

## Models and Relationships

### Message Model

**Relationships:**
- `sender()` - BelongsTo User
- `recipient()` - BelongsTo User

**Methods:**
- `markAsRead()` - Mark message as read
- `isRead()` - Check if message has been read

**Scopes:**
- `between($userId1, $userId2)` - Get messages between two users
- `unread()` - Get only unread messages

### User Model

**New Relationships:**
- `sentMessages()` - HasMany Message (as sender)
- `receivedMessages()` - HasMany Message (as recipient)

## Testing

Run the test suite:

```bash
php artisan test --filter=Message
```

Tests include:
- Message creation and encryption
- Read receipts
- Message relationships
- API endpoints
- Authorization policies
- Validation rules

## Migration

Run the migration to create the messages table:

```bash
php artisan migrate
```

## Future Enhancements

Potential improvements for the messaging system:

1. **Real-time Updates**: Integration with Laravel Echo and WebSockets for live messages
2. **Typing Indicators**: Show when the other user is typing
3. **Message Attachments**: Support for file uploads
4. **Group Messaging**: Extend to support group conversations
5. **Message Search**: Full-text search capabilities
6. **Notifications**: Email/push notifications for new messages
7. **Message Reactions**: Emoji reactions to messages
8. **Message Editing**: Allow users to edit sent messages
9. **Message Deletion**: Soft delete with retention period
10. **Blocked Users**: Prevent messages from blocked users
