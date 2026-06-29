<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authenticated home — users land in the Filament "app" panel (user-facing surface).
Route::get('/dashboard', function () {
    return redirect()->route('filament.app.pages.dashboard');
})->middleware(['auth:sanctum', config('jetstream.auth_session')])->name('dashboard');

require __DIR__.'/socialstream.php';
