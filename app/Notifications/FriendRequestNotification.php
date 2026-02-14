<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $requesterId,
        public string $requesterName,
        public ?string $requesterAvatar = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'requester_id' => $this->requesterId,
            'requester_name' => $this->requesterName,
            'requester_avatar' => $this->requesterAvatar,
            'type' => 'friend_request',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'requester_id' => $this->requesterId,
            'requester_name' => $this->requesterName,
            'requester_avatar' => $this->requesterAvatar,
            'type' => 'friend_request',
        ]);
    }
}
