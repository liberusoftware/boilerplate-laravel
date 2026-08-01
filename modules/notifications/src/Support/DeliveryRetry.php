<?php

namespace Liberu\Foundation\Notifications\Support;

final class DeliveryRetry
{
    public function delay(int $attempt): int
    {
        return min(21600, 60 * (2 ** max(0, min($attempt - 1, 8))));
    }

    public function exhausted(int $attempt): bool
    {
        return $attempt >= 8;
    }
}
