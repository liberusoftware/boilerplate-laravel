<?php

namespace Liberu\Foundation\Identity\Events;

use DateTimeImmutable;

final readonly class IdentityEvent
{
    public DateTimeImmutable $occurredAt;

    /** @param array<string, scalar|null> $context */
    public function __construct(
        public string $name,
        public string|int|null $actorId,
        public array $context = [],
        public string $version = '1.0',
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
