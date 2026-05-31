<?php

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a chat message', function () {
    $user = User::factory()->create();
    $msg = ChatMessage::create(['user_id' => $user->id, 'message' => 'Hello world']);

    expect($msg->message)->toBe('Hello world');
    expect($msg->user_id)->toBe($user->id);
});

it('user relationship resolves correctly', function () {
    $user = User::factory()->create();
    $msg = ChatMessage::create(['user_id' => $user->id, 'message' => 'Test']);

    expect($msg->user)->toBeInstanceOf(User::class);
    expect($msg->user->id)->toBe($user->id);
});
