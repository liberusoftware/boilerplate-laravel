/**
 * Laravel Echo and Pusher configuration for real-time notifications
 */

// Only initialize Echo if broadcasting is enabled (not using 'log' or 'null' driver)
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    import('pusher-js').then((Pusher) => {
        import('laravel-echo').then((Echo) => {
            window.Pusher = Pusher.default;

            window.Echo = new Echo.default({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
                wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1'}.pusher.com`,
                wsPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
                wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
                forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            // Listen for user-specific notifications
            const userId = document.querySelector('meta[name="user-id"]')?.content;
            
            if (userId) {
                // Listen to notifications channel
                window.Echo.private(`notifications.${userId}`)
                    .notification((notification) => {
                        handleNotification(notification);
                    });
            }
        });
    });
}

/**
 * Handle incoming real-time notifications
 * 
 * @param {Object} notification - The notification data
 */
function handleNotification(notification) {
    console.log('Received notification:', notification);
    
    // Dispatch a custom event for the notification
    window.dispatchEvent(new CustomEvent('notification-received', {
        detail: notification
    }));

    // Show browser notification if permission granted
    if ('Notification' in window && Notification.permission === 'granted') {
        const title = getNotificationTitle(notification);
        const options = {
            body: getNotificationBody(notification),
            icon: '/images/notification-icon.png',
            tag: notification.id,
        };
        
        new Notification(title, options);
    }

    // You can also update UI elements here
    updateNotificationBadge();
}

/**
 * Get notification title based on type
 */
function getNotificationTitle(notification) {
    switch (notification.type) {
        case 'new_message':
            return `New message from ${notification.sender_name}`;
        case 'friend_request':
            return `Friend request from ${notification.requester_name}`;
        case 'activity':
            return notification.activity_type;
        default:
            return 'New notification';
    }
}

/**
 * Get notification body based on type
 */
function getNotificationBody(notification) {
    switch (notification.type) {
        case 'new_message':
            return notification.message;
        case 'friend_request':
            return `${notification.requester_name} wants to connect with you`;
        case 'activity':
            return notification.activity_message;
        default:
            return 'You have a new notification';
    }
}

/**
 * Update notification badge count
 */
function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        const currentCount = parseInt(badge.textContent) || 0;
        badge.textContent = currentCount + 1;
        badge.classList.remove('hidden');
    }
}

// Request notification permission on page load
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}
