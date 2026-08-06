<?php

namespace Liberu\Foundation\Search\Registry;

use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

/**
 * The searchable types a composition offers, and how each one is queried.
 *
 * A sibling of {@see IndexableRegistry} rather than a widening of it: that one
 * answers "what does `search:reindex` walk", keyed to an Eloquent class it can
 * chunk, and is filled from `config('search.models')`. This one answers "what
 * can `searchAll()` and `/api/search/all` offer", keyed to a callable whose
 * filters, scopes and security rules belong to the package that owns the
 * concept. A type can be one without the other — a searcher backed by an
 * external index has no model to chunk — so folding them together would force
 * every registrant to supply a half it does not have.
 */
final class SearcherRegistry
{
    /** @var array<string, callable(array<string, mixed>): LengthAwarePaginator<int, mixed>> */
    private array $searchers = [];

    /**
     * @param  callable(array<string, mixed>): LengthAwarePaginator<int, mixed>  $searcher
     */
    public function register(string $type, callable $searcher): void
    {
        if (isset($this->searchers[$type])) {
            throw new InvalidArgumentException("Search type [{$type}] already exists.");
        }

        $this->searchers[$type] = $searcher;
    }

    /**
     * @return array<string, callable(array<string, mixed>): LengthAwarePaginator<int, mixed>>
     */
    public function all(): array
    {
        return $this->searchers;
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->searchers);
    }
}
