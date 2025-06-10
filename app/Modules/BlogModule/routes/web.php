<?php

use App\Modules\BlogModule\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

// Blog module web routes
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/{id}', [BlogController::class, 'show'])->name('show');
});