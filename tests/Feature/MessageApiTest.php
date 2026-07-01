<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

it('can send a message via API', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this->actingAs($sender, 'sanctum')
        ->postJson('/api/messages', [
            'recipient_id' => $recipient->id,
            'body' => 'Test message',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message' => ['id', 'sender_id', 'recipient_id', 'body', 'created_at'],
            'success',
        ]);

    expect(Message::count())->toBe(1);
    // Body must be stored encrypted, never as plaintext.
    expect(Crypt::decryptString(Message::first()->body))->toBe('Test message');
});

it('prevents sending messages to self', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/messages', [
            'recipient_id' => $user->id,
            'body' => 'Message to myself',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['recipient_id']);
});

it('requires authentication to send messages', function () {
    $recipient = User::factory()->create();

    $response = $this->postJson('/api/messages', [
        'recipient_id' => $recipient->id,
        'body' => 'Test message',
    ]);

    $response->assertStatus(401);
});

it('can retrieve conversation between two users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Message::factory()->create([
        'sender_id' => $user1->id,
        'recipient_id' => $user2->id,
        'body' => Crypt::encryptString('Message 1'),
    ]);

    Message::factory()->create([
        'sender_id' => $user2->id,
        'recipient_id' => $user1->id,
        'body' => Crypt::encryptString('Message 2'),
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/messages/{$user2->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'messages' => [
                '*' => ['id', 'sender_id', 'recipient_id', 'body', 'created_at'],
            ],
            'user',
        ]);

    expect($response->json('messages'))->toHaveCount(2);
    // Bodies are decrypted for display.
    expect($response->json('messages.0.body'))->toBe('Message 1');
});

it('marks the other user messages as read when opening a conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $incoming = Message::factory()->unread()->create([
        'sender_id' => $user2->id,
        'recipient_id' => $user1->id,
    ]);

    $this->actingAs($user1, 'sanctum')
        ->getJson("/api/messages/{$user2->id}")
        ->assertStatus(200);

    expect($incoming->fresh()->isRead())->toBeTrue();
});

it('does not leak recipient email in the users list', function () {
    $user = User::factory()->create();
    User::factory()->create(['email' => 'secret@example.com']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/messages/users');

    $response->assertStatus(200)
        ->assertJsonMissing(['email' => 'secret@example.com']);
});

it('lists conversations with a decrypted preview and unread count', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    Message::factory()->create([
        'sender_id' => $me->id,
        'recipient_id' => $other->id,
        'body' => Crypt::encryptString('older'),
        'created_at' => now()->subMinute(),
    ]);
    Message::factory()->unread()->create([
        'sender_id' => $other->id,
        'recipient_id' => $me->id,
        'body' => Crypt::encryptString('newest'),
        'created_at' => now(),
    ]);

    $response = $this->actingAs($me, 'sanctum')->getJson('/api/messages');

    $response->assertStatus(200)
        ->assertJsonCount(1)
        // Preview must be plaintext, not ciphertext.
        ->assertJsonPath('0.last_message.body', 'newest')
        ->assertJsonPath('0.unread_count', 1);
});

it('forbids a non-participant from reading a conversation', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $outsider = User::factory()->create();

    Message::factory()->create(['sender_id' => $a->id, 'recipient_id' => $b->id]);

    // Outsider asking for their thread with $a simply sees an empty thread —
    // never $a<->$b messages.
    $response = $this->actingAs($outsider, 'sanctum')->getJson("/api/messages/{$a->id}");

    $response->assertStatus(200);
    expect($response->json('messages'))->toBeEmpty();
});

it('forbids deleting a message you are not party to', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $outsider = User::factory()->create();

    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
    ]);

    $this->actingAs($outsider, 'sanctum')
        ->deleteJson("/api/messages/{$message->id}")
        ->assertStatus(403);

    expect(Message::find($message->id))->not->toBeNull();
});

it('can mark a message as read', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'read_at' => null,
    ]);

    $response = $this->actingAs($recipient, 'sanctum')
        ->patchJson("/api/messages/{$message->id}/read");

    $response->assertStatus(200);

    expect($message->fresh()->isRead())->toBeTrue();
});

it('prevents unauthorized users from viewing messages', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();

    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
    ]);

    $response = $this->actingAs($otherUser, 'sanctum')
        ->patchJson("/api/messages/{$message->id}/read");

    $response->assertStatus(403);
});

it('can delete own messages', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
    ]);

    $response = $this->actingAs($sender, 'sanctum')
        ->deleteJson("/api/messages/{$message->id}");

    $response->assertStatus(200);

    expect(Message::find($message->id))->toBeNull();
});

it('can get list of users to message', function () {
    $user = User::factory()->create();
    User::factory()->count(5)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/messages/users');

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(5); // Excludes the authenticated user.
});

it('can get unread message count', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Message::factory()->count(3)->create([
        'sender_id' => $user2->id,
        'recipient_id' => $user1->id,
        'read_at' => null,
    ]);

    Message::factory()->count(2)->create([
        'sender_id' => $user2->id,
        'recipient_id' => $user1->id,
        'read_at' => now(),
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/messages/unread-count');

    $response->assertStatus(200)
        ->assertJson(['count' => 3]);
});

it('validates message body is required', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/messages', [
            'recipient_id' => $recipient->id,
            'body' => '',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

it('validates recipient exists', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/messages', [
            'recipient_id' => 99999,
            'body' => 'Test message',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['recipient_id']);
});
