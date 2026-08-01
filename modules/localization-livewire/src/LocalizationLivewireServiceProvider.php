<?php

namespace Liberu\Foundation\LocalizationLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\LocalizationLivewire\Livewire\LanguageSwitcher;
use Livewire\Livewire;

final class LocalizationLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'localization-livewire');
        Livewire::component('language-switcher', LanguageSwitcher::class);
    }
}
