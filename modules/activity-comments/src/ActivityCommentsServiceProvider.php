<?php

namespace Liberu\Foundation\ActivityComments;

use Illuminate\Support\ServiceProvider;

final class ActivityCommentsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
