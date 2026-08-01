<?php

namespace Liberu\Foundation\Observability\Support;

use Liberu\Foundation\Observability\Contracts\Metrics;

final class NullMetrics implements Metrics
{
    public function increment(string $name, int $value = 1, array $labels = []): void {}

    public function observe(string $name, float $value, array $labels = []): void {}
}
