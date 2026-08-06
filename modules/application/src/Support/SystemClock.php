<?php

namespace Liberu\Foundation\ApplicationCore\Support;

use Liberu\Foundation\ApplicationCore\Contracts\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
