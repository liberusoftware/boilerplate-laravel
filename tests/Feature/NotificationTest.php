<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Notifications\FriendRequestNotification;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_message_notification_is_sent(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create(['name' => 'John Doe']);

        Notification::fake();

        $user->notify(new NewMessageNotification(
            messageContent: 'Hello, how are you?',
            senderId: $sender->id,
            senderName: $sender->name
        ));

        Notification::assertSentTo(
            $user,
            NewMessageNotification::class,
            function ($notification) use ($sender) {
                $data = $notification->toArray($notification);
                return $data['sender_id'] === $sender->id &&
                       $data['sender_name'] === $sender->name &&
                       $data['type'] === 'new_message';
            }
        );
    }

    public function test_friend_request_notification_is_sent(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create(['name' => 'Jane Smith']);

        Notification::fake();

        $user->notify(new FriendRequestNotification(
            requesterId: $requester->id,
            requesterName: $requester->name,
            requesterAvatar: $requester->profile_photo_url
        ));

        Notification::assertSentTo(
            $user,
            FriendRequestNotification::class,
            function ($notification) use ($requester) {
                $data = $notification->toArray($notification);
                return $data['requester_id'] === $requester->id &&
                       $data['requester_name'] === $requester->name &&
                       $data['type'] === 'friend_request';
            }
        );
    }

    public function test_activity_notification_is_sent(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Bob Johnson']);

        Notification::fake();

        $user->notify(new ActivityNotification(
            activityType: 'Post Liked',
            activityMessage: 'Bob Johnson liked your post',
            actorId: $actor->id,
            actorName: $actor->name,
            metadata: ['post_id' => 123]
        ));

        Notification::assertSentTo(
            $user,
            ActivityNotification::class,
            function ($notification) use ($actor) {
                $data = $notification->toArray($notification);
                return $data['activity_type'] === 'Post Liked' &&
                       $data['actor_id'] === $actor->id &&
                       $data['type'] === 'activity' &&
                       $data['metadata']['post_id'] === 123;
            }
        );
    }

    public function test_notification_is_stored_in_database(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create(['name' => 'John Doe']);

        $user->notify(new NewMessageNotification(
            messageContent: 'Test message',
            senderId: $sender->id,
            senderName: $sender->name
        ));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => NewMessageNotification::class,
        ]);

        $notification = $user->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('new_message', $notification->data['type']);
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create(['name' => 'John Doe']);

        $user->notify(new NewMessageNotification(
            messageContent: 'Test message',
            senderId: $sender->id,
            senderName: $sender->name
        ));

        $notification = $user->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_all_notifications_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create(['name' => 'John Doe']);

        // Send multiple notifications
        for ($i = 0; $i < 3; $i++) {
            $user->notify(new NewMessageNotification(
                messageContent: "Message $i",
                senderId: $sender->id,
                senderName: $sender->name
            ));
        }

        $this->assertEquals(3, $user->unreadNotifications()->count());

        $user->unreadNotifications->markAsRead();

        $this->assertEquals(0, $user->unreadNotifications()->count());
        $this->assertEquals(3, $user->readNotifications()->count());
    }

    public function test_notification_broadcasts_event(): void
    {
        Event::fake([
            \Illuminate\Notifications\Events\BroadcastNotificationCreated::class,
        ]);

        $user = User::factory()->create();
        $sender = User::factory()->create(['name' => 'John Doe']);

        $user->notify(new NewMessageNotification(
            messageContent: 'Test message',
            senderId: $sender->id,
            senderName: $sender->name
        ));

        Event::assertDispatched(
            \Illuminate\Notifications\Events\BroadcastNotificationCreated::class,
            function ($event) use ($user) {
                return $event->notifiable->id === $user->id;
            }
        );
    }

    public function test_notification_uses_correct_channels(): void
    {
        $notification = new NewMessageNotification(
            messageContent: 'Test',
            senderId: 1,
            senderName: 'Test User'
        );

        $channels = $notification->via(User::factory()->create());

        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    public function test_notification_includes_broadcast_data(): void
    {
        $notification = new NewMessageNotification(
            messageContent: 'Hello World',
            senderId: 123,
            senderName: 'John Doe'
        );

        $broadcastMessage = $notification->toBroadcast(User::factory()->create());
        $data = $broadcastMessage->data;

        $this->assertEquals('Hello World', $data['message']);
        $this->assertEquals(123, $data['sender_id']);
        $this->assertEquals('John Doe', $data['sender_name']);
        $this->assertEquals('new_message', $data['type']);
    }
}
