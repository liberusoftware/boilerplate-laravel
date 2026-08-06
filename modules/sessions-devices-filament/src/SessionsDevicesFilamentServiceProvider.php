<?php

namespace Liberu\Foundation\SessionsDevicesFilament;

use Illuminate\Support\ServiceProvider;

final class SessionsDevicesFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sessions-devices-filament');
    }
}
