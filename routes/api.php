<?php

use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

// Search API routes
Route::prefix('search')->name('search.')->middleware('throttle:60,1')->group(function () {
    Route::get('/users', [SearchController::class, 'users'])->name('users');
    Route::get('/posts', [SearchController::class, 'posts'])->name('posts');
    Route::get('/groups', [SearchController::class, 'groups'])->name('groups');
    Route::get('/all', [SearchController::class, 'all'])->name('all');
});
