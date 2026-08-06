<?php

use Illuminate\Support\Facades\Route;
use Liberu\Foundation\ApplicationCore\Http\Controllers\ReadinessController;

Route::get('/ready', ReadinessController::class)->name('application.ready');
