<?php

namespace Modules\Ping\Providers;

use Illuminate\Support\ServiceProvider;

class PingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        $this->mergeConfigFrom(
            __DIR__.'/../../config/Ping.php',
            strtolower('Ping')
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', strtolower('Ping'));

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', strtolower('Ping'));

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/Ping.php' => config_path(strtolower('Ping').'.php'),
        ], 'Ping-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'Ping-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/'.strtolower('Ping')),
        ], 'Ping-views');
    }
}
