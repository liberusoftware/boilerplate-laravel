<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    /**
     * Search users with advanced filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function searchUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query();

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
     * Search posts with advanced filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Post>
     */
    public function searchPosts(array $filters): LengthAwarePaginator
    {
        $query = Post::query()->with('user');

        // Search by title or content
        if (! empty($filters['query'])) {
            $query->search($this->toString($filters['query']));
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->status($this->toString($filters['status']));
        }

        // Filter by author
        if (! empty($filters['author_id'])) {
            $query->byAuthor($this->toInt($filters['author_id']));
        }

        // Filter by date range
        if (! empty($filters['published_from']) || ! empty($filters['published_to'])) {
            $query->dateRange(
                $filters['published_from'] ?? null,
                $filters['published_to'] ?? null
            );
        }

        // Drafts must never be reachable via search, regardless of caller input.
        $query->published();

        // Order by
        $orderBy = $this->toString($filters['order_by'] ?? 'published_at');
        $orderDirection = ($filters['order_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($this->toInt($filters['per_page'] ?? 15));
    }

    /**
     * Search groups with advanced filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Group>
     */
    public function searchGroups(array $filters): LengthAwarePaginator
    {
        $query = Group::query()->with('owner');

        // Search by name or description
        if (! empty($filters['query'])) {
            $query->search($this->toString($filters['query']));
        }

        // Filter by active status
        if (! empty($filters['active_only'])) {
            $query->active();
        }

        // Only public groups are searchable; private/restricted must never appear.
        $query->type('public');

        // Filter by owner
        if (! empty($filters['owner_id'])) {
            $query->byOwner($this->toInt($filters['owner_id']));
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
     * Search all entities (users, posts, groups) with a single query.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function searchAll(array $filters): array
    {
        $results = [];

        // Limit per entity type
        $perPage = $filters['per_page'] ?? 5;

        // Search users
        if (! isset($filters['types']) || in_array('users', (array) $filters['types'])) {
            $results['users'] = $this->searchUsers(array_merge($filters, ['per_page' => $perPage]));
        }

        // Search posts
        if (! isset($filters['types']) || in_array('posts', (array) $filters['types'])) {
            $results['posts'] = $this->searchPosts(array_merge($filters, ['per_page' => $perPage]));
        }

        // Search groups
        if (! isset($filters['types']) || in_array('groups', (array) $filters['types'])) {
            $results['groups'] = $this->searchGroups(array_merge($filters, ['per_page' => $perPage]));
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
