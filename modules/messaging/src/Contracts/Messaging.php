<?php

namespace Liberu\Messaging\Core\Contracts;

interface Messaging
{
    /** @return list<array<string, mixed>> */
    public function conversations(int|string $actorId): array;

    /** @return list<array<string, mixed>> */
    public function conversation(int|string $actorId, int|string $otherId): array;

    /** @return array<string, mixed> */
    public function send(int|string $actorId, int|string $recipientId, string $body): array;

    /** @return array<string, mixed> */
    public function markRead(int|string $actorId, int|string $messageId): array;

    public function delete(int|string $actorId, int|string $messageId): void;

    public function unreadCount(int|string $actorId): int;
}
