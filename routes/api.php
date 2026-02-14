<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Notification API Routes (Example)
|--------------------------------------------------------------------------
|
| These are example routes for the notification system.
| Uncomment and customize as needed for your application.
|
*/

// Route::middleware('auth:sanctum')->group(function () {
//     // Get unread notifications
//     Route::get('/notifications/unread', [App\Http\Controllers\NotificationExampleController::class, 'getUnreadNotifications']);
//     
//     // Get all notifications (paginated)
//     Route::get('/notifications', [App\Http\Controllers\NotificationExampleController::class, 'getAllNotifications']);
//     
//     // Mark notification as read
//     Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationExampleController::class, 'markAsRead']);
//     
//     // Mark all notifications as read
//     Route::post('/notifications/read-all', [App\Http\Controllers\NotificationExampleController::class, 'markAllAsRead']);
//     
//     // Delete notification
//     Route::delete('/notifications/{id}', [App\Http\Controllers\NotificationExampleController::class, 'deleteNotification']);
// });
