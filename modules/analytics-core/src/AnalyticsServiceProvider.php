<?php

namespace Liberu\Foundation\Analytics;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Analytics\Support\DestinationRegistry;

final class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DestinationRegistry::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
