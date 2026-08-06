<?php

namespace Liberu\Foundation\ThemeSupportLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ThemeSupportLivewire\Livewire\ThemeSwitcher;
use Livewire\Livewire;

final class ThemeSupportLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'theme-support-livewire');
        Livewire::component('theme-switcher', ThemeSwitcher::class);
    }
}
