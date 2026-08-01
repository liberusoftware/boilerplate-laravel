<?php

namespace Liberu\Messaging\Filament;

use Illuminate\Support\ServiceProvider;

final class MessagingFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'messaging-filament');
    }
}
