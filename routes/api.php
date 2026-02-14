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

Route::middleware('auth:sanctum')->group(function () {
    // Message routes
    Route::get('/messages', [App\Http\Controllers\MessageController::class, 'index']);
    Route::get('/messages/users', [App\Http\Controllers\MessageController::class, 'users']);
    Route::get('/messages/unread-count', [App\Http\Controllers\MessageController::class, 'unreadCount']);
    Route::get('/messages/{user}', [App\Http\Controllers\MessageController::class, 'show']);
    Route::post('/messages', [App\Http\Controllers\MessageController::class, 'store']);
    Route::patch('/messages/{message}/read', [App\Http\Controllers\MessageController::class, 'markAsRead']);
    Route::delete('/messages/{message}', [App\Http\Controllers\MessageController::class, 'destroy']);
});
