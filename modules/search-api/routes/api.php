<?php

use Illuminate\Support\Facades\Route;
use Liberu\Foundation\SearchApi\Http\Controllers\SearchController;

Route::prefix('api/search')->name('search.')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/users', [SearchController::class, 'users'])->name('users');
    Route::get('/all', [SearchController::class, 'all'])->name('all');
});
