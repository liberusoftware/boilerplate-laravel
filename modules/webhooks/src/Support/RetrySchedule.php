<?php

namespace Liberu\Foundation\Webhooks\Support;

final class RetrySchedule
{
    public function seconds(int $attempt): int
    {
        return min(86400, 30 * (2 ** max(0, min($attempt - 1, 11))));
    }
}
