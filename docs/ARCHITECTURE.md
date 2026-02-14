# Real-Time Notification System Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    NOTIFICATION FLOW DIAGRAM                            │
└─────────────────────────────────────────────────────────────────────────┘

1. TRIGGER EVENT
   ┌──────────────────────────────────────────┐
   │   User Action / System Event             │
   │   (e.g., New Message, Friend Request)    │
   └──────────────┬───────────────────────────┘
                  │
                  ▼
2. NOTIFICATION DISPATCH
   ┌──────────────────────────────────────────┐
   │   $user->notify(                         │
   │     new NewMessageNotification(...)      │
   │   );                                     │
   └──────────────┬───────────────────────────┘
                  │
                  ├────────────────┬──────────────────┐
                  ▼                ▼                  ▼
3. CHANNELS   [Database]      [Broadcast]      [Queue]
              ┌──────────┐    ┌───────────┐    ┌──────────┐
              │ Store    │    │ Pusher    │    │ Process  │
              │ in DB    │    │ WebSocket │    │ in BG    │
              └────┬─────┘    └─────┬─────┘    └────┬─────┘
                   │                │                │
                   ▼                ▼                ▼
4. DELIVERY   [Persistent]    [Real-time]      [Optimized]
              ┌──────────┐    ┌───────────┐    ┌──────────┐
              │ Can view │    │ Instant   │    │ Non-     │
              │ history  │    │ delivery  │    │ blocking │
              └──────────┘    └─────┬─────┘    └──────────┘
                                    │
                                    ▼
5. CLIENT SIDE
   ┌──────────────────────────────────────────┐
   │   Laravel Echo Listener                  │
   │   (resources/js/app.js)                  │
   └──────────────┬───────────────────────────┘
                  │
                  ├────────────────┬──────────────────┐
                  ▼                ▼                  ▼
6. PRESENTATION
   ┌──────────┐    ┌───────────┐    ┌──────────────┐
   │ Browser  │    │ Custom    │    │ Update UI    │
   │ Notif.   │    │ Event     │    │ Badge/Toast  │
   └──────────┘    └───────────┘    └──────────────┘

═══════════════════════════════════════════════════════════════════════════

COMPONENTS OVERVIEW

Backend:
┌─────────────────────────────────────────────────────────────┐
│ Broadcasting Infrastructure                                 │
├─────────────────────────────────────────────────────────────┤
│ • BroadcastServiceProvider (enabled)                        │
│ • Private channels (routes/channels.php)                    │
│ • Pusher configuration (.env)                               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Notification Classes (app/Notifications/)                   │
├─────────────────────────────────────────────────────────────┤
│ • NewMessageNotification                                    │
│ • FriendRequestNotification                                 │
│ • ActivityNotification                                      │
│                                                             │
│ Features:                                                   │
│ ✓ ShouldQueue (background processing)                       │
│ ✓ Database channel (persistence)                            │
│ ✓ Broadcast channel (real-time)                             │
│ ✓ Custom toBroadcast() method                               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Database Schema                                             │
├─────────────────────────────────────────────────────────────┤
│ notifications table:                                        │
│ • id (uuid)                                                 │
│ • type (string)                                             │
│ • notifiable (polymorphic)                                  │
│ • data (json)                                               │
│ • read_at (timestamp)                                       │
│ • timestamps                                                │
└─────────────────────────────────────────────────────────────┘

Frontend:
┌─────────────────────────────────────────────────────────────┐
│ JavaScript Dependencies (package.json)                      │
├─────────────────────────────────────────────────────────────┤
│ • laravel-echo: ^1.16.1                                     │
│ • pusher-js: ^8.4.0-rc2                                     │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Notification Listener (resources/js/app.js)                 │
├─────────────────────────────────────────────────────────────┤
│ • Auto-initializes Laravel Echo                             │
│ • Subscribes to user notification channel                   │
│ • Dispatches custom events                                  │
│ • Shows browser notifications                               │
│ • Updates UI badges                                         │
│ • Requests notification permissions                         │
└─────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════════════

CHANNEL AUTHORIZATION

Private Channel: notifications.{userId}
┌─────────────────────────────────────────────────────────────┐
│ Broadcast::channel('notifications.{userId}', function (...) │
│ {                                                           │
│     return (int) $user->id === (int) $userId;               │
│ });                                                         │
│                                                             │
│ Security: Only authenticated user can listen to their own   │
│           notification channel                              │
└─────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════════════

USAGE EXAMPLE

PHP (Backend):
┌─────────────────────────────────────────────────────────────┐
│ use App\Notifications\NewMessageNotification;               │
│                                                             │
│ $user->notify(new NewMessageNotification(                   │
│     messageContent: 'Hello!',                               │
│     senderId: auth()->id(),                                 │
│     senderName: auth()->user()->name                        │
│ ));                                                         │
└─────────────────────────────────────────────────────────────┘

JavaScript (Frontend):
┌─────────────────────────────────────────────────────────────┐
│ window.addEventListener('notification-received', (event) => │
│ {                                                           │
│     const notification = event.detail;                      │
│     showToast(notification.message);                        │
│     updateBadge();                                          │
│ });                                                         │
└─────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════════════

TESTING

Feature Tests (tests/Feature/NotificationTest.php):
┌─────────────────────────────────────────────────────────────┐
│ ✓ Notification delivery                                     │
│ ✓ Database persistence                                      │
│ ✓ Broadcasting events                                       │
│ ✓ Read/unread management                                    │
│ ✓ Bulk operations                                           │
│ ✓ Channel configuration                                     │
│ ✓ Data structure validation                                 │
└─────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════════════

PERFORMANCE OPTIMIZATIONS

1. Queue Processing
   • All notifications implement ShouldQueue
   • Background processing via Laravel queue
   • Non-blocking notification dispatch

2. Efficient Broadcasting
   • Private channels reduce overhead
   • Targeted delivery to specific users
   • Conditional frontend initialization

3. Database Optimization
   • UUID primary keys for distributed systems
   • Indexed columns for fast queries
   • JSON data type for flexible storage

4. Frontend Optimization
   • Lazy loading of Echo/Pusher libraries
   • Browser notification caching
   • Event-driven UI updates

═══════════════════════════════════════════════════════════════════════════

SECURITY FEATURES

✓ Private channel authorization
✓ CSRF protection (Laravel Echo)
✓ SSL/TLS enforcement
✓ Input validation
✓ Rate limiting support
✓ No vulnerabilities (CodeQL verified)

═══════════════════════════════════════════════════════════════════════════

DOCUMENTATION

• docs/NOTIFICATIONS.md - Comprehensive setup & usage guide
• IMPLEMENTATION_SUMMARY.md - Implementation details
• README.md - Feature overview
• Inline code documentation
```
