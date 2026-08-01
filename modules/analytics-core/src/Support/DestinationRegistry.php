<?php

namespace Liberu\Foundation\Analytics\Support;

use InvalidArgumentException;
use Liberu\Foundation\Analytics\Contracts\AnalyticsDestination;

final class DestinationRegistry
{
    private array $destinations = [];

    public function register(AnalyticsDestination $destination): void
    {
        if (isset($this->destinations[$destination->name()])) {
            throw new InvalidArgumentException('Duplicate analytics destination.');
        }$this->destinations[$destination->name()] = $destination;
    }

    public function get(string $name): AnalyticsDestination
    {
        return $this->destinations[$name] ?? throw new InvalidArgumentException("Unknown analytics destination [{$name}].");
    }

    public function all(): array
    {
        return $this->destinations;
    }
}
