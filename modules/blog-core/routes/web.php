<?php

use Illuminate\Support\Facades\Route;
use Liberu\Blog\Core\Http\Controllers\BlogController;

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
