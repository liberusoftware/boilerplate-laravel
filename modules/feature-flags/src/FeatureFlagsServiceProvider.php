<?php

namespace Liberu\Foundation\FeatureFlags;

use Illuminate\Support\ServiceProvider;

final class FeatureFlagsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
