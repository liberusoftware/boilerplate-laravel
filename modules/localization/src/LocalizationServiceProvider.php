<?php

namespace Liberu\Foundation\Localization;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Localization\Context\LocaleResolver;
use Liberu\Foundation\Localization\Translation\TranslationRegistry;
use Liberu\Localization\Contracts\TranslationProviderRegistry;

final class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/localization.php', 'localization');
        $this->app->singleton(LocaleResolver::class);
        $this->app->singleton(TranslationRegistry::class);
        $this->app->alias(TranslationRegistry::class, TranslationProviderRegistry::class);
    }

    public function boot(): void {}
}
