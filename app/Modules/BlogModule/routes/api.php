<?php

use App\Modules\BlogModule\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

// Blog module API routes
Route::prefix('api/blog')->name('api.blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::post('/', [BlogController::class, 'store'])->name('store');
    Route::get('/{id}', [BlogController::class, 'show'])->name('show');
});