<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Notifications\FriendRequestNotification;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;

/**
 * Example controller demonstrating notification usage
 * 
 * This is a reference implementation showing how to use the notification system.
 * You can integrate these patterns into your actual controllers.
 */
class NotificationExampleController extends Controller
{
    /**
     * Example: Send a new message notification
     */
    public function sendMessageNotification(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string|max:500',
        ]);

        $recipient = User::findOrFail($request->recipient_id);
        
        // Send notification
        $recipient->notify(new NewMessageNotification(
            messageContent: $request->message,
            senderId: auth()->id(),
            senderName: auth()->user()->name
        ));

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    }

    /**
     * Example: Send a friend request notification
     */
    public function sendFriendRequest(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        
        // Send friend request notification
        $user->notify(new FriendRequestNotification(
            requesterId: auth()->id(),
            requesterName: auth()->user()->name,
            requesterAvatar: auth()->user()->profile_photo_url
        ));

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent'
        ]);
    }

    /**
     * Example: Send a generic activity notification
     */
    public function sendActivityNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'activity_type' => 'required|string',
            'activity_message' => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);
        
        // Send activity notification
        $user->notify(new ActivityNotification(
            activityType: $request->activity_type,
            activityMessage: $request->activity_message,
            actorId: auth()->id(),
            actorName: auth()->user()->name,
            metadata: $request->input('metadata', [])
        ));

        return response()->json([
            'success' => true,
            'message' => 'Activity notification sent'
        ]);
    }

    /**
     * Get user's unread notifications
     */
    public function getUnreadNotifications()
    {
        $notifications = auth()->user()->unreadNotifications()
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get all user notifications (paginated)
     */
    public function getAllNotifications(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $notifications = auth()->user()->notifications()
            ->paginate($perPage);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $notification = auth()->user()->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($notificationId)
    {
        $notification = auth()->user()->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Example: Bulk send notifications to multiple users
     */
    public function sendBulkNotification(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'activity_type' => 'required|string',
            'activity_message' => 'required|string',
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        
        foreach ($users as $user) {
            $user->notify(new ActivityNotification(
                activityType: $request->activity_type,
                activityMessage: $request->activity_message,
                actorId: auth()->id(),
                actorName: auth()->user()->name,
                metadata: $request->input('metadata', [])
            ));
        }

        return response()->json([
            'success' => true,
            'message' => "Notification sent to {$users->count()} users"
        ]);
    }
}
