<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search users with advanced filters.
     */
    public function users(Request $request): JsonResponse
    {
        $filters = $this->validateUserFilters($request);
        $results = $this->searchService->searchUsers($filters);

        return response()->json($results);
    }

    /**
     * Search posts with advanced filters.
     */
    public function posts(Request $request): JsonResponse
    {
        $filters = $this->validatePostFilters($request);
        $results = $this->searchService->searchPosts($filters);

        return response()->json($results);
    }

    /**
     * Search groups with advanced filters.
     */
    public function groups(Request $request): JsonResponse
    {
        $filters = $this->validateGroupFilters($request);
        $results = $this->searchService->searchGroups($filters);

        return response()->json($results);
    }

    /**
     * Search all entities with advanced filters.
     */
    public function all(Request $request): JsonResponse
    {
        $filters = $this->validateAllFilters($request);
        $results = $this->searchService->searchAll($filters);

        return response()->json($results);
    }

    /**
     * Validate user search filters.
     */
    protected function validateUserFilters(Request $request): array
    {
        return $request->validate([
            'query' => 'nullable|string|max:255',
            'role' => 'nullable|string',
            'verified' => 'nullable|boolean',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
            'order_by' => 'nullable|in:name,email,created_at',
            'order_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }

    /**
     * Validate post search filters.
     */
    protected function validatePostFilters(Request $request): array
    {
        return $request->validate([
            'query' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,published,archived',
            'author_id' => 'nullable|integer|exists:users,id',
            'published_from' => 'nullable|date',
            'published_to' => 'nullable|date',
            'include_drafts' => 'nullable|boolean',
            'order_by' => 'nullable|in:title,published_at,created_at',
            'order_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }

    /**
     * Validate group search filters.
     */
    protected function validateGroupFilters(Request $request): array
    {
        return $request->validate([
            'query' => 'nullable|string|max:255',
            'type' => 'nullable|in:public,private,restricted',
            'owner_id' => 'nullable|integer|exists:users,id',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
            'order_by' => 'nullable|in:name,created_at',
            'order_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }

    /**
     * Validate all search filters.
     */
    protected function validateAllFilters(Request $request): array
    {
        return $request->validate([
            'query' => 'nullable|string|max:255',
            'types' => 'nullable|array',
            'types.*' => 'in:users,posts,groups',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }
}
