<?php

namespace Liberu\Analytics\Google;

use Illuminate\Support\ServiceProvider;
use Liberu\Analytics\Contracts\AnalyticsDestinationRegistry;
use Liberu\Analytics\Google\Contracts\GoogleTransport;
use Liberu\Analytics\Google\Support\GoogleDestination;

final class AnalyticsGoogleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->bound(GoogleTransport::class)) {
            $this->app->make(AnalyticsDestinationRegistry::class)->register($this->app->make(GoogleDestination::class));
        }
    }
}
