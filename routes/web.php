<?php

use App\Models\User;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\Inertia\ProfilePhotoController;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Health check endpoint (used by Docker, Kubernetes, and Octane)
Route::get('/up', fn () => response()->json(['status' => 'ok']))->name('health');

Route::get('/', fn () => view('welcome'));

// Theme demo page
Route::get('/theme-demo', fn () => view('theme-demo'))->name('theme.demo');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/chat', fn () => view('chat'))->name('chat');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', fn () => view('home'))->name('home');

    Route::get('/messages', function () {
        return view('messages.index');
    })->name('messages.index');

    Route::get('/messages/{user}', function ($userId) {
        $user = User::findOrFail($userId);

        return view('messages.show', compact('user'));
    })->name('messages.show');
});

Route::delete('/user/profile-photo', [ProfilePhotoController::class, 'destroy'])
    ->middleware(['auth', AuthenticateSession::class])
    ->name('current-user-photo.destroy');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');
