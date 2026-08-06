<?php

namespace Liberu\Foundation\Search\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Liberu\Foundation\Search\Registry\SearcherRegistry;

class SearchService
{
    public function __construct(private readonly SearcherRegistry $searchers) {}

    /**
     * Search users with advanced filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function searchUsers(array $filters): LengthAwarePaginator
    {
        $query = config('search.models.user')::query();

        // Search by name or email
        if (! empty($filters['query'])) {
            $query->search($this->toString($filters['query']));
        }

        // Filter by role
        if (! empty($filters['role'])) {
            $query->role($this->toString($filters['role']));
        }

        // Filter by email verification
        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by date range
        if (! empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        if (! empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        // Order by
        $orderBy = $this->toString($filters['order_by'] ?? 'created_at');
        $orderDirection = ($filters['order_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($this->toInt($filters['per_page'] ?? 15));
    }

    /**
     * Search every registered type with a single query.
     *
     * The set of types is whatever the composition registered, not a list held
     * here: naming them in this body is what tied `search` to the demo that
     * owned posts and groups. An unrequested or unregistered type is absent from
     * the result rather than present and empty, so a caller can tell "this
     * composition has no such type" from "no matches".
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, LengthAwarePaginator<int, mixed>>
     */
    public function searchAll(array $filters): array
    {
        $requested = isset($filters['types']) ? (array) $filters['types'] : null;
        $filters['per_page'] = $filters['per_page'] ?? 5;

        $results = [];

        foreach ($this->searchers->all() as $type => $searcher) {
            if ($requested === null || in_array($type, $requested, true)) {
                $results[$type] = $searcher($filters);
            }
        }

        return $results;
    }

    /**
     * Coerce a mixed filter value to a string for query binding.
     */
    private function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Coerce a mixed filter value to an int for query binding.
     */
    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
