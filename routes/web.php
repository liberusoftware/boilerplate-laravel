<?php

use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
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

Route::get('/', fn () => view('welcome'));

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/chat', fn () => view('chat'))->name('chat');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/messages', function () {
        return view('messages.index');
    })->name('messages.index');
    
    Route::get('/messages/{user}', function ($userId) {
        $user = \App\Models\User::findOrFail($userId);
        return view('messages.show', compact('user'));
    })->name('messages.show');
});

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');

require __DIR__.'/socialstream.php';
