<?php

namespace Liberu\Foundation\TwoFactor;

use Illuminate\Support\ServiceProvider;

final class TwoFactorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/two-factor.php', 'two-factor');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/two-factor.php' => config_path('two-factor.php')], 'two-factor-config');
    }
}
