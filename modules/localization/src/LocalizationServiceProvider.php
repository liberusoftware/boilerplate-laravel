<?php

namespace Liberu\Foundation\Localization;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Localization\Context\LocaleResolver;
use Liberu\Foundation\Localization\Livewire\LanguageSwitcher;
use Livewire\Livewire;

final class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/localization.php', 'localization');
        $this->app->singleton(LocaleResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'localization');
        Livewire::component('language-switcher', LanguageSwitcher::class);
    }
}
