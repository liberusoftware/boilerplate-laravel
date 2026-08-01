<?php

namespace Liberu\Foundation\Filament;

use Illuminate\Support\ServiceProvider;

final class FoundationFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'foundation-filament');
    }
}
