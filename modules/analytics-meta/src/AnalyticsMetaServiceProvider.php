<?php

namespace Liberu\Foundation\Analytics\Meta;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Analytics\Meta\Contracts\MetaTransport;
use Liberu\Foundation\Analytics\Meta\Support\MetaDestination;
use Liberu\Foundation\Analytics\Support\DestinationRegistry;

final class AnalyticsMetaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->bound(MetaTransport::class)) {
            $this->app->make(DestinationRegistry::class)->register($this->app->make(MetaDestination::class));
        }
    }
}
