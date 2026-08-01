<?php

namespace Liberu\Messaging\Core\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Crypt;
use Liberu\Messaging\Core\Contracts\Messaging;
use Liberu\Messaging\Core\Models\Message;

final class EloquentMessaging implements Messaging
{
    public function conversations(int|string $actorId): array
    {
        return Message::query()
            ->where(fn ($query) => $query->where('sender_id', $actorId)->orWhere('recipient_id', $actorId))
            ->latest()
            ->get()
            ->groupBy(fn (Message $message) => (string) ($message->sender_id == $actorId ? $message->recipient_id : $message->sender_id))
            ->map(function ($messages, string $participantId) use ($actorId): array {
                $last = $messages->first();

                return [
                    'participant_id' => $participantId,
                    'last_message' => $this->present($last),
                    'unread_count' => $messages->where('recipient_id', $actorId)->whereNull('read_at')->count(),
                ];
            })->values()->all();
    }

    public function conversation(int|string $actorId, int|string $otherId): array
    {
        $messages = Message::between((int) $actorId, (int) $otherId)->oldest()->get();
        Message::query()->where('sender_id', $otherId)->where('recipient_id', $actorId)->whereNull('read_at')->update(['read_at' => now()]);

        return $messages->map(fn (Message $message): array => $this->present($message))->all();
    }

    public function send(int|string $actorId, int|string $recipientId, string $body): array
    {
        $message = Message::query()->create([
            'sender_id' => $actorId,
            'recipient_id' => $recipientId,
            'body' => Crypt::encryptString($body),
        ]);

        return $this->present($message, $body);
    }

    public function markRead(int|string $actorId, int|string $messageId): array
    {
        $message = Message::query()->findOrFail($messageId);
        if ((string) $message->recipient_id !== (string) $actorId) {
            throw new AuthorizationException();
        }
        $message->markAsRead();

        return $this->present($message);
    }

    public function delete(int|string $actorId, int|string $messageId): void
    {
        $message = Message::query()->findOrFail($messageId);
        if (! in_array((string) $actorId, [(string) $message->sender_id, (string) $message->recipient_id], true)) {
            throw new AuthorizationException();
        }
        $message->delete();
    }

    public function unreadCount(int|string $actorId): int
    {
        return Message::query()->where('recipient_id', $actorId)->whereNull('read_at')->count();
    }

    /** @return array<string, mixed> */
    private function present(Message $message, ?string $plainBody = null): array
    {
        return [
            'id' => $message->getKey(),
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'body' => $plainBody ?? Crypt::decryptString($message->body),
            'read_at' => $message->read_at?->toISOString(),
            'created_at' => $message->created_at?->toISOString(),
        ];
    }
}
