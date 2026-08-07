<?php

namespace Liberu\Foundation\Search\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * The query scope `SearchService` calls on the configured user model.
 *
 * Without this the package was incomplete: `searchUsers()` calls `->search()`,
 * but the scope only existed on the host's own `App\Models\User`. Any
 * application installing this package and pointing `search.models.user` at its
 * own model got `Call to undefined method` on the first search.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
trait Searchable
{
    /**
     * Match the term against the columns this model considers searchable.
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        // Grouped, so an added filter cannot be swallowed by the ORs.
        return $query->where(function (Builder $q) use ($search): void {
            foreach ($this->searchableColumns() as $index => $column) {
                $index === 0
                    ? $q->where($column, 'like', "%{$search}%")
                    : $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Override to search different columns.
     *
     * @return list<string>
     */
    public function searchableColumns(): array
    {
        return ['name', 'email'];
    }
}
