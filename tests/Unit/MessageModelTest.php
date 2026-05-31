<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

it('has fillable attributes', function () {
    $msg = new Message;
    expect($msg->getFillable())->toContain('sender_id', 'recipient_id', 'body', 'read_at');
});

it('casts read_at as datetime', function () {
    $casts = (new Message)->getCasts();
    expect($casts)->toHaveKey('read_at');
});

it('sender relationship resolves correctly', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $msg = Message::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'body' => Crypt::encryptString('hello')]);

    expect($msg->sender->id)->toBe($sender->id);
});

it('recipient relationship resolves correctly', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $msg = Message::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'body' => Crypt::encryptString('hi')]);

    expect($msg->recipient->id)->toBe($recipient->id);
});

it('isRead returns false when read_at is null', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $msg = Message::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'body' => Crypt::encryptString('test'), 'read_at' => null]);

    expect($msg->isRead())->toBeFalse();
});

it('markAsRead sets read_at timestamp', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $msg = Message::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'body' => Crypt::encryptString('test'), 'read_at' => null]);

    $msg->markAsRead();
    expect($msg->fresh()->isRead())->toBeTrue();
    expect($msg->fresh()->read_at)->not->toBeNull();
});

it('scopeBetween returns messages between two users bidirectionally', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();

    Message::create(['sender_id' => $u1->id, 'recipient_id' => $u2->id, 'body' => Crypt::encryptString('a')]);
    Message::create(['sender_id' => $u2->id, 'recipient_id' => $u1->id, 'body' => Crypt::encryptString('b')]);
    Message::create(['sender_id' => $u3->id, 'recipient_id' => $u1->id, 'body' => Crypt::encryptString('c')]);

    $between = Message::between($u1->id, $u2->id)->get();
    expect($between)->toHaveCount(2);
});

it('scopeUnread only returns unread messages', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    Message::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'body' => Crypt::encryptString('a'), 'read_at' => null]);
    Message::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'body' => Crypt::encryptString('b'), 'read_at' => now()]);

    $unread = Message::unread()->where('recipient_id', $recipient->id)->get();
    expect($unread)->toHaveCount(1);
});
