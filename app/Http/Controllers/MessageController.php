<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    /**
     * List the authenticated user's conversations, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $conversations = Message::query()
            ->where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->with(['sender', 'recipient'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (Message $message): int => $message->sender_id === $user->id
                ? $message->recipient_id
                : $message->sender_id)
            ->map(function (Collection $messages, int|string $userId) use ($user): array {
                /** @var Message $last */
                $last = $messages->first();
                // Decrypt the preview body so the conversation list isn't ciphertext.
                $last->body = Crypt::decryptString($last->body);

                return [
                    'user' => User::find((int) $userId),
                    'last_message' => $last,
                    'unread_count' => $messages
                        ->where('recipient_id', $user->id)
                        ->whereNull('read_at')
                        ->count(),
                ];
            })
            ->values();

        return response()->json($conversations);
    }

    /**
     * Show the conversation between the authenticated user and another user,
     * marking the other user's messages as read.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $messages = Message::between($authUser->id, $user->id)
            ->with(['sender', 'recipient'])
            ->orderBy('created_at')
            ->get()
            ->map(function (Message $message): Message {
                $message->body = Crypt::decryptString($message->body);

                return $message;
            });

        Message::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $authUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages,
            'user' => $user,
        ]);
    }

    /**
     * Store a new message, encrypting the body at rest.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        /** @var User $authUser */
        $authUser = $request->user();

        if ($authUser->id === (int) $validated['recipient_id']) {
            throw ValidationException::withMessages([
                'recipient_id' => ['You cannot send messages to yourself.'],
            ]);
        }

        $message = Message::create([
            'sender_id' => $authUser->id,
            'recipient_id' => (int) $validated['recipient_id'],
            'body' => Crypt::encryptString($validated['body']),
        ]);

        $message->load(['sender', 'recipient']);
        // Echo the plaintext back for immediate display; storage stays encrypted.
        $message->body = $validated['body'];

        return response()->json([
            'message' => $message,
            'success' => true,
        ], 201);
    }

    /**
     * Mark a message as read (recipient only).
     */
    public function markAsRead(Request $request, Message $message): JsonResponse
    {
        $this->authorize('view', $message);

        if ($request->user()?->id !== $message->recipient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Delete a message the authenticated user sent or received.
     */
    public function destroy(Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }

    /**
     * List users available to message (everyone but the authenticated user).
     */
    public function users(Request $request): JsonResponse
    {
        $users = User::query()
            ->whereKeyNot($request->user()?->getKey())
            ->select('id', 'name', 'profile_photo_path')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * Unread message count for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Message::query()
            ->where('recipient_id', $request->user()?->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
