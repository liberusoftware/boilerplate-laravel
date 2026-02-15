# Private Messaging System - Setup Instructions

## Prerequisites

- PHP 8.4 or higher
- Composer
- MySQL/PostgreSQL database
- Node.js and npm (for frontend assets)

## Installation Steps

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and configure your database:

```bash
cp .env.example .env
```

Update the following in your `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Migrations

```bash
php artisan migrate
```

This will create the `messages` table along with all other required tables.

### 5. Compile Assets

```bash
npm run build
```

For development:
```bash
npm run dev
```

### 6. Start the Application

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Using the Messaging System

### Web Interface

1. **Login** to your account at `/login`
2. **Navigate to Messages** at `/messages`
3. **Start a new conversation** by clicking "New Message"
4. **Select a user** from the dropdown
5. **Type your message** and click "Send"

### API Endpoints

All API endpoints require authentication via Laravel Sanctum.

#### Send a Message

```bash
curl -X POST http://localhost:8000/api/messages \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_id": 2,
    "body": "Hello, this is my message"
  }'
```

#### Get Conversations

```bash
curl http://localhost:8000/api/messages \
  -H "Accept: application/json"
```

#### Get Messages with Specific User

```bash
curl http://localhost:8000/api/messages/2 \
  -H "Accept: application/json"
```

## Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run only messaging tests
php artisan test --filter=Message
```

## Security Notes

1. **Message Encryption**: All message bodies are encrypted in the database using Laravel's encryption
2. **Authorization**: Users can only access their own messages
3. **Authentication**: All routes require authentication
4. **CSRF Protection**: All forms include CSRF tokens
5. **Input Validation**: All inputs are validated and sanitized

## Troubleshooting

### Issue: "Class not found" errors

**Solution**: Run `composer dump-autoload`

### Issue: Database connection errors

**Solution**: 
- Check your `.env` database configuration
- Ensure the database exists
- Verify database credentials

### Issue: "Key not found" error

**Solution**: Run `php artisan key:generate`

### Issue: Messages not appearing

**Solution**: 
- Check that migrations ran successfully
- Verify authentication is working
- Check browser console for JavaScript errors

## Development

To work on the messaging system:

1. **Models**: `app/Models/Message.php`
2. **Controllers**: `app/Http/Controllers/MessageController.php`
3. **Views**: `resources/views/messages/`
4. **Routes**: 
   - API: `routes/api.php`
   - Web: `routes/web.php`
5. **Tests**: `tests/Feature/Message*.php`

## Additional Documentation

See `MESSAGING.md` for detailed API documentation and usage examples.
