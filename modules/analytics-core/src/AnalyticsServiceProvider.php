<?php

namespace Liberu\Analytics\Core;

use Illuminate\Support\ServiceProvider;
use Liberu\Analytics\Contracts\AnalyticsDestinationRegistry;
use Liberu\Analytics\Core\Support\DestinationRegistry;

final class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DestinationRegistry::class);
        $this->app->alias(DestinationRegistry::class, AnalyticsDestinationRegistry::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
