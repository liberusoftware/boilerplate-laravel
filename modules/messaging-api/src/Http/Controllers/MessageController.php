<?php

namespace Liberu\Messaging\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Liberu\Messaging\Core\Contracts\Messaging;

final class MessageController
{
    public function __construct(private readonly Messaging $messaging) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->messaging->conversations($request->user()->getAuthIdentifier()));
    }

    public function show(Request $request, int $user): JsonResponse
    {
        $userModel = config('auth.providers.users.model');

        return response()->json([
            'messages' => $this->messaging->conversation($request->user()->getAuthIdentifier(), $user),
            'user' => $userModel::query()->findOrFail($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['recipient_id' => 'required|exists:users,id', 'body' => 'required|string|max:5000']);
        $actorId = $request->user()->getAuthIdentifier();
        if ((string) $actorId === (string) $validated['recipient_id']) {
            throw ValidationException::withMessages(['recipient_id' => ['You cannot send messages to yourself.']]);
        }

        return response()->json([
            'message' => $this->messaging->send($actorId, $validated['recipient_id'], $validated['body']),
            'success' => true,
        ], 201);
    }

    public function markAsRead(Request $request, int $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $this->messaging->markRead($request->user()->getAuthIdentifier(), $message),
        ]);
    }

    public function destroy(Request $request, int $message): JsonResponse
    {
        $this->messaging->delete($request->user()->getAuthIdentifier(), $message);

        return response()->json(['success' => true, 'message' => 'Message deleted successfully']);
    }

    public function users(Request $request): JsonResponse
    {
        $userModel = config('auth.providers.users.model');
        $users = $userModel::query()->whereKeyNot($request->user()?->getKey())->select('id', 'name', 'profile_photo_path')->orderBy('name')->get();

        return response()->json($users);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->messaging->unreadCount($request->user()->getAuthIdentifier())]);
    }
}
