<?php

use App\Livewire\ThemeSwitcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('theme switcher renders with default theme', function () {
    Livewire::test(ThemeSwitcher::class)
        ->assertSet('currentTheme', 'default');
});

test('theme switcher can switch to dark theme', function () {
    Livewire::test(ThemeSwitcher::class)
        ->call('switchTheme', 'dark')
        ->assertSet('currentTheme', 'dark');
});

test('theme switcher ignores unknown themes', function () {
    Livewire::test(ThemeSwitcher::class)
        ->call('switchTheme', 'nonexistent')
        ->assertSet('currentTheme', 'default');
});

test('theme switcher loads available themes on mount', function () {
    Livewire::test(ThemeSwitcher::class)
        ->assertSet('availableThemes', function ($themes) {
            return is_array($themes) && count($themes) > 0;
        });
});
