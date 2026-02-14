<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    /**
     * Search users with advanced filters.
     */
    public function searchUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query();

        // Search by name or email
        if (!empty($filters['query'])) {
            $query->search($filters['query']);
        }

        // Filter by role
        if (!empty($filters['role'])) {
            $query->role($filters['role']);
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
        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Search posts with advanced filters.
     */
    public function searchPosts(array $filters): LengthAwarePaginator
    {
        $query = Post::query()->with('author');

        // Search by title or content
        if (!empty($filters['query'])) {
            $query->search($filters['query']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        // Filter by author
        if (!empty($filters['author_id'])) {
            $query->byAuthor($filters['author_id']);
        }

        // Filter by date range
        if (!empty($filters['published_from']) || !empty($filters['published_to'])) {
            $query->dateRange(
                $filters['published_from'] ?? null,
                $filters['published_to'] ?? null
            );
        }

        // Only published posts (unless explicitly requesting all)
        if (!isset($filters['include_drafts']) || !$filters['include_drafts']) {
            $query->published();
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'published_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Search groups with advanced filters.
     */
    public function searchGroups(array $filters): LengthAwarePaginator
    {
        $query = Group::query()->with('owner');

        // Search by name or description
        if (!empty($filters['query'])) {
            $query->search($filters['query']);
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->type($filters['type']);
        }

        // Filter by owner
        if (!empty($filters['owner_id'])) {
            $query->byOwner($filters['owner_id']);
        }

        // Filter by date range
        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Search all entities (users, posts, groups) with a single query.
     */
    public function searchAll(array $filters): array
    {
        $results = [];

        // Limit per entity type
        $perPage = $filters['per_page'] ?? 5;

        // Search users
        if (!isset($filters['types']) || in_array('users', $filters['types'])) {
            $results['users'] = $this->searchUsers(array_merge($filters, ['per_page' => $perPage]));
        }

        // Search posts
        if (!isset($filters['types']) || in_array('posts', $filters['types'])) {
            $results['posts'] = $this->searchPosts(array_merge($filters, ['per_page' => $perPage]));
        }

        // Search groups
        if (!isset($filters['types']) || in_array('groups', $filters['types'])) {
            $results['groups'] = $this->searchGroups(array_merge($filters, ['per_page' => $perPage]));
        }

        return $results;
    }
}
