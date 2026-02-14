<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

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

    // Create messages
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
    expect($response->json())->toHaveCount(5); // Excludes the authenticated user
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
