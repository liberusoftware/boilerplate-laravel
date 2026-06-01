<?php

use App\Livewire\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('chat component mounts with empty message', function () {
    Livewire::test(Chat::class)
        ->assertSet('message', '');
});

it('chat component loads existing messages', function () {
    ChatMessage::factory()->count(3)->create();

    Livewire::test(Chat::class)
        ->assertViewHas('chatMessages', function ($messages) {
            return $messages->count() === 3;
        });
});

it('sending a message clears the input', function () {
    Livewire::test(Chat::class)
        ->set('message', 'Hello world')
        ->call('sendMessage')
        ->assertSet('message', '');
});

it('chat message is persisted to database', function () {
    Livewire::test(Chat::class)
        ->set('message', 'Test message content')
        ->call('sendMessage');

    expect(ChatMessage::where('message', 'Test message content')->exists())->toBeTrue();
});

it('validates message is required', function () {
    Livewire::test(Chat::class)
        ->set('message', '')
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'required']);
});

it('validates message max length', function () {
    Livewire::test(Chat::class)
        ->set('message', str_repeat('x', 501))
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'max']);
});
