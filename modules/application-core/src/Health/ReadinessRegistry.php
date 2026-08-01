<?php

namespace Liberu\Foundation\ApplicationCore\Health;

use InvalidArgumentException;

final class ReadinessRegistry
{
    private array $checks = [];

    public function register(ReadinessCheck $check): void
    {
        if (isset($this->checks[$check->name()])) {
            throw new InvalidArgumentException('Duplicate readiness check.');
        }$this->checks[$check->name()] = $check;
    }

    public function report(): array
    {
        return array_map(fn (ReadinessCheck $check): bool => $check->ready(), $this->checks);
    }

    public function ready(): bool
    {
        return ! in_array(false, $this->report(), true);
    }
}
