<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserSearchController extends Controller
{
    /**
     * Search users by name or email with optimized query.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $request->input('query');
        $perPage = $request->input('per_page', 15);

        // Optimized search query with selective column loading
        $users = User::search($query)
            ->select(['id', 'name', 'email', 'profile_photo_path', 'created_at'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
