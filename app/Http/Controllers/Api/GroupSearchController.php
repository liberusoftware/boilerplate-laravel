<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GroupSearchController extends Controller
{
    /**
     * Search groups by name or description with optimized query.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'active_only' => 'sometimes|boolean',
        ]);

        $query = $request->input('query');
        $perPage = $request->input('per_page', 15);
        $activeOnly = $request->input('active_only', false);

        // Optimized search query with selective column loading
        $groups = Group::search($query)
            ->select(['id', 'name', 'description', 'is_active', 'created_at', 'updated_at'])
            ->when($activeOnly, fn ($q) => $q->active())
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }
}
