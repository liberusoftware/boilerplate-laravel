<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

beforeEach(function () {
    // Run migrations
    $this->artisan('migrate:fresh');
});

it('can create a message between users', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $messageBody = 'Hello, this is a test message';
    $encryptedBody = Crypt::encryptString($messageBody);

    $message = Message::create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => $encryptedBody,
    ]);

    expect($message)->toBeInstanceOf(Message::class);
    expect($message->sender_id)->toBe($sender->id);
    expect($message->recipient_id)->toBe($recipient->id);
    expect(Crypt::decryptString($message->body))->toBe($messageBody);
    expect($message->isRead())->toBeFalse();
});

it('can mark a message as read', function () {
    $message = Message::factory()->create([
        'read_at' => null,
    ]);

    expect($message->isRead())->toBeFalse();

    $message->markAsRead();

    expect($message->fresh()->isRead())->toBeTrue();
});

it('can get messages between two users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    // Create messages between user1 and user2
    Message::factory()->create([
        'sender_id' => $user1->id,
        'recipient_id' => $user2->id,
    ]);

    Message::factory()->create([
        'sender_id' => $user2->id,
        'recipient_id' => $user1->id,
    ]);

    // Create a message from user3 (should not be included)
    Message::factory()->create([
        'sender_id' => $user3->id,
        'recipient_id' => $user1->id,
    ]);

    $messagesBetween = Message::between($user1->id, $user2->id)->get();

    expect($messagesBetween)->toHaveCount(2);
});

it('can scope unread messages', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Message::factory()->create([
        'sender_id' => $user1->id,
        'recipient_id' => $user2->id,
        'read_at' => null,
    ]);

    Message::factory()->create([
        'sender_id' => $user1->id,
        'recipient_id' => $user2->id,
        'read_at' => now(),
    ]);

    $unreadMessages = Message::where('recipient_id', $user2->id)
        ->unread()
        ->get();

    expect($unreadMessages)->toHaveCount(1);
});

it('has sender and recipient relationships', function () {
    $sender = User::factory()->create(['name' => 'John Doe']);
    $recipient = User::factory()->create(['name' => 'Jane Smith']);

    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
    ]);

    expect($message->sender->name)->toBe('John Doe');
    expect($message->recipient->name)->toBe('Jane Smith');
});
