<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostSearchController extends Controller
{
    /**
     * Search posts by title or content with optimized query.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|string|in:draft,published,archived',
        ]);

        $query = $request->input('query');
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');

        // Optimized search query with eager loading and selective columns
        $posts = Post::search($query)
            ->with('user:id,name,email')
            ->select(['id', 'user_id', 'title', 'content', 'status', 'created_at', 'updated_at'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }
}
