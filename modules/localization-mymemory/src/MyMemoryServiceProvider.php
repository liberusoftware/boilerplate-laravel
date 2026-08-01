<?php

namespace Liberu\Foundation\Localization\MyMemory;

use Illuminate\Support\ServiceProvider;
use Liberu\Localization\Contracts\TranslationProviderRegistry;

final class MyMemoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(TranslationProviderRegistry::class)->register($this->app->make(TranslationService::class));
    }
}
