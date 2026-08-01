<?php

namespace Liberu\Foundation\Sessions;

use Illuminate\Support\ServiceProvider;

final class SessionsDevicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sessions-devices.php', 'sessions-devices');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
