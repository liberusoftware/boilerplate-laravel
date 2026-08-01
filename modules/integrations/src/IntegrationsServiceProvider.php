<?php

namespace Liberu\Foundation\Integrations;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Integrations\Support\IntegrationRegistry;

final class IntegrationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IntegrationRegistry::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
