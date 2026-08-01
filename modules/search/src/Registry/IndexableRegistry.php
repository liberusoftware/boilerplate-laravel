<?php

namespace Liberu\Foundation\Search\Registry;

use InvalidArgumentException;

final class IndexableRegistry
{
    private array $models = [];

    public function register(string $type, string $model): void
    {
        if (isset($this->models[$type])) {
            throw new InvalidArgumentException("Search type [{$type}] already exists.");
        }$this->models[$type] = $model;
    }

    public function all(): array
    {
        return $this->models;
    }
}
