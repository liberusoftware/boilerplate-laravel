<?php

namespace Liberu\Foundation\Search\Services;

use Illuminate\Database\Eloquent\Model;
use Liberu\Foundation\Search\Contracts\SearchIndexer;

final class LocalSearchIndexer implements SearchIndexer
{
    public function index(string $type, Model $record): void {}

    public function remove(string $type, string|int $id): void {}

    public function flush(string $type): void {}
}
