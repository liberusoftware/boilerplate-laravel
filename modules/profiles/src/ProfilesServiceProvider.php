<?php

namespace Liberu\Foundation\Profiles;

use Illuminate\Support\ServiceProvider;

final class ProfilesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
