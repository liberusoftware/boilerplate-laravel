<?php

namespace Liberu\Foundation\Analytics\Google;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Analytics\Google\Contracts\GoogleTransport;
use Liberu\Foundation\Analytics\Google\Support\GoogleDestination;
use Liberu\Foundation\Analytics\Support\DestinationRegistry;

final class AnalyticsGoogleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->bound(GoogleTransport::class)) {
            $this->app->make(DestinationRegistry::class)->register($this->app->make(GoogleDestination::class));
        }
    }
}
