<?php

namespace Liberu\Foundation\Observability\Support;

use InvalidArgumentException;

final class SloRegistry
{
    private array $objectives = [];

    public function register(string $name, float $target, string $window): void
    {
        if (isset($this->objectives[$name]) || $target <= 0 || $target > 1) {
            throw new InvalidArgumentException('Invalid or duplicate SLO.');
        }$this->objectives[$name] = compact('target', 'window');
    }

    public function all(): array
    {
        return $this->objectives;
    }
}
