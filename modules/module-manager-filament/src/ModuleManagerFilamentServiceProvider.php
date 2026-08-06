<?php

namespace Liberu\Foundation\ModuleManagerFilament;

use Illuminate\Support\ServiceProvider;

final class ModuleManagerFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-manager-filament');
    }
}
