<?php

use App\Http\Controllers\Api\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\PostSearchController;
use App\Http\Controllers\Api\GroupSearchController;

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

// Search endpoints with rate limiting for performance
Route::prefix('search')->middleware('throttle:60,1')->group(function () {
    Route::get('/users', [UserSearchController::class, 'search'])->name('api.search.users');
    Route::get('/posts', [PostSearchController::class, 'search'])->name('api.search.posts');
    Route::get('/groups', [GroupSearchController::class, 'search'])->name('api.search.groups');
// Search API routes
Route::prefix('search')->name('search.')->group(function () {
    Route::get('/users', [SearchController::class, 'users'])->name('users');
    Route::get('/posts', [SearchController::class, 'posts'])->name('posts');
    Route::get('/groups', [SearchController::class, 'groups'])->name('groups');
    Route::get('/all', [SearchController::class, 'all'])->name('all');
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
