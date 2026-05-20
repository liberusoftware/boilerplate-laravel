# Real-Time Notifications Documentation

## Overview

This Laravel boilerplate includes a comprehensive real-time notification system using Laravel's broadcasting features with Pusher (or compatible services).

## Features

- ✅ Real-time notification delivery via websockets
- ✅ Database-backed notification history
- ✅ Browser push notifications support
- ✅ Multiple notification types (messages, friend requests, activities)
- ✅ Optimized delivery mechanism with queued processing
- ✅ Private user channels for security

## Setup Instructions

### 1. Install Pusher PHP SDK

```bash
composer require pusher/pusher-php-server
```

### 2. Install Frontend Dependencies

```bash
npm install
```

This will install:
- `laravel-echo` - Laravel's broadcasting client
- `pusher-js` - Pusher JavaScript SDK

### 3. Configure Environment Variables

Update your `.env` file with Pusher credentials:

```env
BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=database  # or redis for better performance

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

**Alternative Broadcasting Services:**
- Ably: Set `BROADCAST_DRIVER=ably` and configure Ably credentials
- Laravel Reverb: Use Laravel's first-party WebSocket server
- Soketi: Self-hosted Pusher alternative

### 4. Run Migrations

```bash
php artisan migrate
```

This creates the `notifications` table for persisting notifications.

### 5. Build Frontend Assets

```bash
npm run build
# or for development
npm run dev
```

### 6. Add User ID Meta Tag

In your main layout file (e.g., `resources/views/layouts/app.blade.php`), add:

```blade
<head>
    <!-- ... other meta tags ... -->
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
</head>
```

### 7. Start Queue Worker (Production)

For production environments, run a queue worker to process notifications:

```bash
php artisan queue:work
```

Use a process manager like Supervisor to keep the queue worker running.

## Usage

### Sending Notifications

#### New Message Notification

```php
use App\Notifications\NewMessageNotification;

$user->notify(new NewMessageNotification(
    messageContent: 'Hello! How are you?',
    senderId: auth()->id(),
    senderName: auth()->user()->name
));
```

#### Friend Request Notification

```php
use App\Notifications\FriendRequestNotification;

$user->notify(new FriendRequestNotification(
    requesterId: auth()->id(),
    requesterName: auth()->user()->name,
    requesterAvatar: auth()->user()->profile_photo_url
));
```

#### Activity Notification

```php
use App\Notifications\ActivityNotification;

$user->notify(new ActivityNotification(
    activityType: 'Post Liked',
    activityMessage: 'John Doe liked your post',
    actorId: $actor->id,
    actorName: $actor->name,
    metadata: ['post_id' => $post->id]
));
```

### Listening to Notifications on Frontend

The notification system automatically handles incoming notifications through `resources/js/app.js`.

You can listen to the `notification-received` custom event:

```javascript
window.addEventListener('notification-received', (event) => {
    const notification = event.detail;
    console.log('New notification:', notification);
    
    // Update your UI
    showNotificationToast(notification);
});
```

### Retrieving User Notifications

```php
// Get all notifications
$notifications = auth()->user()->notifications;

// Get unread notifications
$unread = auth()->user()->unreadNotifications;

// Mark notification as read
$notification->markAsRead();

// Mark all as read
auth()->user()->unreadNotifications->markAsRead();
```

## Notification Types

### 1. NewMessageNotification
- **Purpose**: Notify users of new messages
- **Channels**: database, broadcast
- **Data**: message content, sender ID, sender name

### 2. FriendRequestNotification
- **Purpose**: Notify users of friend requests
- **Channels**: database, broadcast
- **Data**: requester ID, name, avatar

### 3. ActivityNotification
- **Purpose**: Generic notification for various activities
- **Channels**: database, broadcast
- **Data**: activity type, message, actor info, metadata

## Creating Custom Notifications

Generate a new notification class:

```bash
php artisan make:notification YourCustomNotification
```

Implement `ShouldBroadcast` for real-time delivery:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class YourCustomNotification extends Notification implements ShouldQueue
{
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => 'your notification data',
            'type' => 'custom_type',
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'data' => 'your notification data',
            'type' => 'custom_type',
        ];
    }
}
```

## Broadcasting Channels

The following private channels are configured in `routes/channels.php`:

- `App.Models.User.{id}` - Default user channel
- `user.{userId}` - General user updates
- `notifications.{userId}` - User-specific notifications

## Testing Notifications

### Test Notification Delivery

```php
use Illuminate\Support\Facades\Notification;

/** @test */
public function it_sends_new_message_notification()
{
    $user = User::factory()->create();
    $sender = User::factory()->create();

    Notification::fake();

    $user->notify(new NewMessageNotification(
        messageContent: 'Test message',
        senderId: $sender->id,
        senderName: $sender->name
    ));

    Notification::assertSentTo(
        $user,
        NewMessageNotification::class
    );
}
```

### Test Broadcasting

```php
use Illuminate\Support\Facades\Event;

/** @test */
public function it_broadcasts_notification()
{
    Event::fake([
        \Illuminate\Notifications\Events\BroadcastNotificationCreated::class,
    ]);

    $user = User::factory()->create();
    $user->notify(new NewMessageNotification(
        messageContent: 'Test',
        senderId: 1,
        senderName: 'Test User'
    ));

    Event::assertDispatched(
        \Illuminate\Notifications\Events\BroadcastNotificationCreated::class
    );
}
```

## Performance Optimization

1. **Use Queue Workers**: All notifications implement `ShouldQueue` for background processing
2. **Optimize Broadcasting**: Use Redis queue driver for better performance
3. **Rate Limiting**: Consider implementing rate limiting for notification-heavy features
4. **Batch Notifications**: Group similar notifications to reduce overhead
5. **Notification Preferences**: Allow users to customize notification settings

## Browser Notifications

The system automatically requests browser notification permission. Users will see a prompt on first visit. Notifications will be displayed even when the tab is not active.

## Troubleshooting

### Notifications Not Broadcasting

1. Check `BROADCAST_DRIVER` is set to `pusher` (or your chosen driver)
2. Verify Pusher credentials in `.env`
3. Ensure BroadcastServiceProvider is enabled in `bootstrap/providers.php`
4. Run `php artisan queue:work` if using queued notifications
5. Check browser console for JavaScript errors

### Frontend Not Receiving Notifications

1. Verify the user-id meta tag is present in your layout
2. Check browser console for Echo connection errors
3. Ensure Vite environment variables are properly set
4. Rebuild frontend assets: `npm run build`

### Debugging

Enable broadcasting event logging:

```php
// In config/logging.php
'channels' => [
    'broadcasting' => [
        'driver' => 'single',
        'path' => storage_path('logs/broadcasting.log'),
        'level' => 'debug',
    ],
],
```

## Security Considerations

1. **Channel Authorization**: All notification channels are private and require authentication
2. **CSRF Protection**: Laravel Echo automatically includes CSRF tokens
3. **SSL/TLS**: Always use HTTPS in production (`PUSHER_SCHEME=https`)
4. **Input Validation**: Sanitize notification data before sending
5. **Rate Limiting**: Implement rate limits on notification endpoints

## Additional Resources

- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel Notifications Documentation](https://laravel.com/docs/notifications)
- [Pusher Documentation](https://pusher.com/docs)
- [Laravel Echo Documentation](https://laravel.com/docs/broadcasting#client-side-installation)

## Support

For issues or questions about the notification system, please create an issue in the repository.
