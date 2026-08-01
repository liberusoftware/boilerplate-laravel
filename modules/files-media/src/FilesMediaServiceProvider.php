<?php

namespace Liberu\Foundation\Files;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Files\Contracts\MalwareScanner;
use Liberu\Foundation\Files\Support\RejectingMalwareScanner;

final class FilesMediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MalwareScanner::class, RejectingMalwareScanner::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
