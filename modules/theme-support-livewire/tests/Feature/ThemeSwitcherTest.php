<?php

use Liberu\Foundation\ThemeSupportLivewire\Livewire\ThemeSwitcher;
use Livewire\Livewire;

/*
 * The switcher offers whatever themes the application has, so the suite
 * dev-requires two of them: `theme-support` discovers `liberu-theme` Composer
 * packages, which is the only way a package that is not a composition can have
 * themes at all.
 */

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
