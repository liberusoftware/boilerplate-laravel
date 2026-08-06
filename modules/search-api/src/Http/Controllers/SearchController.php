<?php

namespace Liberu\Foundation\SearchApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Liberu\Foundation\Search\Registry\SearcherRegistry;
use Liberu\Foundation\Search\Services\SearchService;

class SearchController
{
    protected SearchService $searchService;

    protected SearcherRegistry $searchers;

    public function __construct(SearchService $searchService, SearcherRegistry $searchers)
    {
        $this->searchService = $searchService;
        $this->searchers = $searchers;
    }

    /**
     * Search users with advanced filters.
     */
    public function users(Request $request): JsonResponse
    {
        $filters = $this->validateUserFilters($request);
        $results = $this->searchService->searchUsers($filters);

        return response()->json($this->projectUsers($results));
    }

    /**
     * Search all entities with advanced filters.
     */
    public function all(Request $request): JsonResponse
    {
        $filters = $this->validateAllFilters($request);
        $results = $this->searchService->searchAll($filters);

        if (isset($results['users']) && $results['users'] instanceof LengthAwarePaginator) {
            $results['users'] = $this->projectUsers($results['users']);
        }

        return response()->json($results);
    }

    /**
     * Project user results to a public, non-PII shape (no email / verification timestamp),
     * so the public search endpoints can't be used to harvest emails.
     *
     * @param  LengthAwarePaginator<int, Model>  $users
     * @return LengthAwarePaginator<int, array{id: int, name: string, profile_photo_url: string}>
     */
    private function projectUsers(LengthAwarePaginator $users): LengthAwarePaginator
    {
        return $users->through(fn (Model $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'profile_photo_url' => $user->profile_photo_url,
        ]);
    }

    /**
     * Validate user search filters.
     *
     * @return array<string, mixed>
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
     * Validate all search filters.
     *
     * The accepted types come from the registry rather than a literal, so a
     * package contributing a searchable concept does not also have to patch this
     * rule to make it reachable.
     *
     * @return array<string, mixed>
     */
    protected function validateAllFilters(Request $request): array
    {
        return $request->validate([
            'query' => 'nullable|string|max:255',
            'types' => 'nullable|array',
            'types.*' => Rule::in($this->searchers->types()),
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }
}
