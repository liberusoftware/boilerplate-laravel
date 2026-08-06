<?php

namespace Liberu\Analytics\Meta;

use Illuminate\Support\ServiceProvider;
use Liberu\Analytics\Contracts\AnalyticsDestinationRegistry;
use Liberu\Analytics\Meta\Contracts\MetaTransport;
use Liberu\Analytics\Meta\Support\MetaDestination;

final class AnalyticsMetaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->bound(MetaTransport::class)) {
            $this->app->make(AnalyticsDestinationRegistry::class)->register($this->app->make(MetaDestination::class));
        }
    }
}
