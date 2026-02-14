<?php

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
});
