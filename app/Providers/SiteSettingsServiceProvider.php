<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Raza9798\SiteConfig\SiteConfig;

class SiteSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('siteconfig', function () {
            return new SiteConfig();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
