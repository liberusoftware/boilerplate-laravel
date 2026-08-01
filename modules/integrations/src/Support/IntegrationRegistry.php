<?php

namespace Liberu\Foundation\Integrations\Support;

use InvalidArgumentException;
use Liberu\Foundation\Integrations\Contracts\IntegrationAdapter;

final class IntegrationRegistry
{
    private array $adapters = [];

    public function register(IntegrationAdapter $adapter): void
    {
        if (isset($this->adapters[$adapter->name()])) {
            throw new InvalidArgumentException('Duplicate integration adapter.');
        }$this->adapters[$adapter->name()] = $adapter;
    }

    public function get(string $name): IntegrationAdapter
    {
        return $this->adapters[$name] ?? throw new InvalidArgumentException("Unknown integration [{$name}].");
    }

    public function all(): array
    {
        return $this->adapters;
    }
}
