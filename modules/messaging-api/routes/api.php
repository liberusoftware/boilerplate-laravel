<?php

use Illuminate\Support\Facades\Route;
use Liberu\Messaging\Api\Http\Controllers\MessageController;

Route::middleware('auth:sanctum')->prefix('api/messages')->name('messages.')->group(function () {
    Route::get('/', [MessageController::class, 'index'])->name('index');
    Route::get('/users', [MessageController::class, 'users'])->name('users');
    Route::get('/unread-count', [MessageController::class, 'unreadCount'])->name('unread-count');
    Route::get('/{user}', [MessageController::class, 'show'])->whereNumber('user')->name('show');
    Route::post('/', [MessageController::class, 'store'])->name('store');
    Route::patch('/{message}/read', [MessageController::class, 'markAsRead'])->name('read');
    Route::delete('/{message}', [MessageController::class, 'destroy'])->name('destroy');
});
