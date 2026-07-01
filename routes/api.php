<?php

use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

// Search API routes
Route::prefix('search')->name('search.')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/users', [SearchController::class, 'users'])->name('users');
    Route::get('/posts', [SearchController::class, 'posts'])->name('posts');
    Route::get('/groups', [SearchController::class, 'groups'])->name('groups');
    Route::get('/all', [SearchController::class, 'all'])->name('all');
});

// Private messaging (literal segments must precede the {user}/{message} wildcards).
Route::middleware('auth:sanctum')->prefix('messages')->name('messages.')->group(function () {
    Route::get('/', [MessageController::class, 'index'])->name('index');
    Route::get('/users', [MessageController::class, 'users'])->name('users');
    Route::get('/unread-count', [MessageController::class, 'unreadCount'])->name('unread-count');
    Route::get('/{user}', [MessageController::class, 'show'])->name('show');
    Route::post('/', [MessageController::class, 'store'])->name('store');
    Route::patch('/{message}/read', [MessageController::class, 'markAsRead'])->name('read');
    Route::delete('/{message}', [MessageController::class, 'destroy'])->name('destroy');
});
