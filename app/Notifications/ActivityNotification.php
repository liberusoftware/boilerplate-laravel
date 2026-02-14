<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $activityType,
        public string $activityMessage,
        public ?int $actorId = null,
        public ?string $actorName = null,
        public ?array $metadata = null
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
            'activity_type' => $this->activityType,
            'activity_message' => $this->activityMessage,
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'metadata' => $this->metadata,
            'type' => 'activity',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'activity_type' => $this->activityType,
            'activity_message' => $this->activityMessage,
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'metadata' => $this->metadata,
            'type' => 'activity',
        ]);
    }
}
