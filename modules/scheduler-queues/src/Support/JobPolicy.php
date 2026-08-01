<?php

namespace Liberu\Foundation\SchedulerQueues\Support;

use InvalidArgumentException;

final class JobPolicy
{
    public function backoff(int $attempt): int
    {
        return min(3600, 10 * (2 ** max(0, min($attempt - 1, 8))));
    }

    public function assertIdempotencyKey(?string $key): string
    {
        if ($key === null || trim($key) === '') {
            throw new InvalidArgumentException('Queued mutations require an idempotency key.');
        }

        return trim($key);
    }
}
