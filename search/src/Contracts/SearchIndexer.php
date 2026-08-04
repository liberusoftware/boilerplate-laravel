<?php

namespace Liberu\Foundation\Search\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SearchIndexer
{
    public function index(string $type, Model $record): void;

    public function remove(string $type, string|int $id): void;

    public function flush(string $type): void;
}
