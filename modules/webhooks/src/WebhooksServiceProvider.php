<?php

namespace Liberu\Foundation\Webhooks;

use Illuminate\Support\ServiceProvider;

final class WebhooksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
