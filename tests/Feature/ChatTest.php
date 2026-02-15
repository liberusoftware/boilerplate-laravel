<?php

use App\Events\MessageSent;
use App\Livewire\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can load messages', function () {
    ChatMessage::factory()->count(3)->create();

    Livewire::test(Chat::class)
        ->assertViewHas('messages', function ($messages) {
            return $messages->count() === 3;
        });
});

it('can send a message', function () {
    Livewire::test(Chat::class)
        ->set('message', 'Hello, World!')
        ->call('sendMessage')
        ->assertSet('message', '');

    expect(ChatMessage::count())->toBe(1);
    expect(ChatMessage::first()->message)->toBe('Hello, World!');
    expect(ChatMessage::first()->user_id)->toBe($this->user->id);
});

it('validates message is required', function () {
    Livewire::test(Chat::class)
        ->set('message', '')
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'required']);
});

it('validates message max length', function () {
    Livewire::test(Chat::class)
        ->set('message', str_repeat('a', 501))
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'max']);
});

it('broadcasts message when sent', function () {
    Event::fake();

    $message = ChatMessage::create([
        'user_id' => $this->user->id,
        'message' => 'Test message',
    ]);

    $message->load('user');

    broadcast(new MessageSent($message));

    Event::assertDispatched(MessageSent::class);
});
