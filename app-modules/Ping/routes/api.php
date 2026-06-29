<?php

use Illuminate\Support\Facades\Route;
use Modules\Ping\Http\Controllers\PingController;

Route::middleware(['api'])->prefix('api')->group(function () {
    Route::get('/ping', [PingController::class, 'index']);
});
