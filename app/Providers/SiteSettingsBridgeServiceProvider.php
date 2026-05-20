<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Settings\SiteSettings as SpatieSiteSettings;

class SiteSettingsBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('siteconfig', function ($app) {
            return $app->make(SpatieSiteSettings::class);
        });
    }

    public function boot(): void
    {
        // No-op
    }
}
