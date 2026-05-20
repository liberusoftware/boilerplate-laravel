<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    /**
     * Display a listing of conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get all users the authenticated user has conversed with
        $conversations = Message::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->with(['sender', 'recipient'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($user) {
                return $message->sender_id === $user->id 
                    ? $message->recipient_id 
                    : $message->sender_id;
            })
            ->map(function ($messages, $userId) {
                $lastMessage = $messages->first();
                $unreadCount = $messages->where('recipient_id', Auth::id())
                    ->whereNull('read_at')
                    ->count();
                
                return [
                    'user' => User::find($userId),
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                ];
            })
            ->values();

        return response()->json($conversations);
    }

    /**
     * Display messages between authenticated user and another user.
     */
    public function show(Request $request, User $user)
    {
        $authUser = Auth::user();
        
        // Get all messages between the two users
        $messages = Message::between($authUser->id, $user->id)
            ->with(['sender', 'recipient'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                // Decrypt the message body for display
                $message->body = Crypt::decryptString($message->body);
                return $message;
            });

        // Mark messages from the other user as read
        Message::where('sender_id', $user->id)
            ->where('recipient_id', $authUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages,
            'user' => $user,
        ]);
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        $authUser = Auth::user();

        // Prevent sending messages to self
        if ($authUser->id === (int) $validated['recipient_id']) {
            throw ValidationException::withMessages([
                'recipient_id' => ['You cannot send messages to yourself.'],
            ]);
        }

        // Encrypt message body for security
        $encryptedBody = Crypt::encryptString($validated['body']);

        $message = Message::create([
            'sender_id' => $authUser->id,
            'recipient_id' => $validated['recipient_id'],
            'body' => $encryptedBody,
        ]);

        // Load relationships and decrypt for response
        $message->load(['sender', 'recipient']);
        $message->body = $validated['body']; // Return unencrypted for immediate display

        return response()->json([
            'message' => $message,
            'success' => true,
        ], 201);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Message $message)
    {
        $this->authorize('view', $message);
        
        if (Auth::id() !== $message->recipient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Remove the specified message.
     */
    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }

    /**
     * Get list of users to message.
     */
    public function users(Request $request)
    {
        $authUser = Auth::user();
        
        // Get all users except the authenticated user
        $users = User::where('id', '!=', $authUser->id)
            ->select('id', 'name', 'email', 'profile_photo_path')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * Get unread message count for authenticated user.
     */
    public function unreadCount(Request $request)
    {
        $count = Message::where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
