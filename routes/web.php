<?php

use App\Http\Controllers\TeamInvitationController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('home');
});

Route::post('/send-invitation', [TeamInvitationController::class, 'sendInvitation'])->name('send.invitation');
Route::post('/accept-invitation/{token}', [TeamInvitationController::class, 'acceptInvitation'])->name('accept.invitation');

// Route::redirect('/register', '/admin/register')->name('register');

Route::get('/privacy', function () {
    return view('pages.privacy');
})->name('privacy');
Route::get('/terms-and-conditions', function () {
    return view('pages.termsandconditions');
})->name('terms.and.conditions');

Route::get('/about', function () {
    return view('pages.aboutus');
})->name('about');

Route::get('/contact', function () { return view('contact'); });
Route::post('/contact/send', 'App\Http\Controllers\ContactController@sendEmail');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
