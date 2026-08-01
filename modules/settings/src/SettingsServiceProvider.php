<?php

namespace Liberu\Foundation\Settings;

use Illuminate\Support\ServiceProvider;

final class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../database/settings' => database_path('settings')], 'settings-migrations');
    }
}
