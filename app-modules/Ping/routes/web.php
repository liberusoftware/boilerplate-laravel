<?php

use Illuminate\Support\Facades\Route;
use Modules\Ping\Http\Controllers\PingController;

Route::middleware(['web'])->group(function () {
    Route::get('/ping', [PingController::class, 'index'])->name('ping.index');
});
