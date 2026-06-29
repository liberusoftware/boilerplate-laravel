# Real-Time Chat Feature

## Overview
This Laravel application now includes a real-time chat feature that allows authenticated users to communicate instantly using WebSockets.

## Features
- Real-time message delivery using Laravel Broadcasting
- WebSocket support via Pusher/Laravel Echo
- Clean, responsive chat interface built with Livewire
- Message history (last 50 messages)
- User identification for each message
- Timestamps for all messages

## Setup Instructions

### 1. Install Dependencies
The necessary packages are already included in `composer.json` and `package.json`:
- Laravel Echo (JavaScript)
- Pusher JS (JavaScript)
- Laravel Reverb is included for local WebSocket server

### 2. Configure Broadcasting

Update your `.env` file with the appropriate broadcast driver:

```env
BROADCAST_DRIVER=pusher

# For Pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1

# Vite variables
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

For local development with Laravel Reverb (recommended):
```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 3. Run Migrations
```bash
php artisan migrate
```

This will create the `chat_messages` table.

### 4. Start the WebSocket Server

#### Using Pusher
No additional server needed. Just configure your Pusher credentials.

#### Using Laravel Reverb (Local Development)
```bash
php artisan reverb:start
```

### 5. Build Assets
```bash
npm install
npm run build
```

For development with hot reload:
```bash
npm run dev
```

### 6. Start the Application
```bash
php artisan serve
```

## Usage

1. **Access the Chat**: Navigate to `/chat` route (requires authentication)
2. **Send Messages**: Type your message in the input field and click "Send" or press Enter
3. **Real-time Updates**: Messages from other users will appear automatically without page refresh
4. **Message History**: The chat loads the last 50 messages on page load

## Technical Details

### Database Schema
The `chat_messages` table includes:
- `id`: Primary key
- `user_id`: Foreign key to users table
- `message`: Text field for the message content
- `created_at` and `updated_at`: Timestamps

### Broadcasting Channel
- Channel: `chat` (public channel)
- Event: `MessageSent`
- Payload includes: message ID, user ID, user name, message content, and timestamp

### Files Modified/Created
- **Models**: `app/Models/ChatMessage.php`
- **Events**: `app/Events/MessageSent.php`
- **Livewire**: `app/Livewire/Chat.php`
- **Views**: `resources/views/livewire/chat.blade.php`, `resources/views/chat.blade.php`
- **Routes**: Updated `routes/web.php` and `routes/channels.php`
- **Migrations**: `database/migrations/2026_02_14_124607_create_chat_messages_table.php`
- **JavaScript**: `resources/js/bootstrap.js`
- **Tests**: `tests/Feature/ChatTest.php`

## Security Considerations

- All chat routes require authentication
- Messages are associated with authenticated users
- Input validation prevents messages over 500 characters
- XSS protection through Blade's automatic escaping

## Customization

### Change Message Limit
In `app/Livewire/Chat.php`, modify the `loadMessages()` method:
```php
->take(50) // Change this number
```

### Styling
The chat interface uses Tailwind CSS. Modify `resources/views/livewire/chat.blade.php` to customize the appearance.

### Private Chat Rooms
To implement private chat rooms, modify the broadcasting channel in `app/Events/MessageSent.php`:
```php
return [
    new PrivateChannel('chat.' . $roomId),
];
```

## Testing

Run the chat feature tests:
```bash
php artisan test tests/Feature/ChatTest.php
```

Or with Pest:
```bash
vendor/bin/pest tests/Feature/ChatTest.php
```

## Troubleshooting

### Messages Not Appearing in Real-Time
1. Verify the WebSocket server is running
2. Check browser console for connection errors
3. Ensure `.env` variables are set correctly
4. Clear Laravel cache: `php artisan config:clear`

### Build Errors
1. Clear node_modules: `rm -rf node_modules && npm install`
2. Clear build cache: `npm run build -- --force`

### Database Errors
1. Run migrations: `php artisan migrate`
2. Check database connection in `.env`
