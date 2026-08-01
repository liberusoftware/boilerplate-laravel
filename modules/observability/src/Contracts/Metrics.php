<?php

namespace Liberu\Foundation\Observability\Contracts;

interface Metrics
{
    public function increment(string $name, int $value = 1, array $labels = []): void;

    public function observe(string $name, float $value, array $labels = []): void;
}
