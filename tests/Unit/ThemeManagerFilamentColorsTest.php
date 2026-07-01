<?php

use App\Services\ThemeManager;
use Filament\Support\Colors\Color;

it('maps the default theme primary color to the Amber Filament palette', function () {
    $colors = app(ThemeManager::class)->getFilamentColors('default');

    expect($colors)->toHaveKey('primary');
    expect($colors['primary'])->toBe(Color::Amber);
});

it('maps the dark theme primary color to the Indigo Filament palette', function () {
    $colors = app(ThemeManager::class)->getFilamentColors('dark');

    expect($colors['primary'])->toBe(Color::Indigo);
});

it('falls back to Amber for an unknown color name', function () {
    // A theme with no colors block resolves to the Amber default.
    $colors = app(ThemeManager::class)->getFilamentColors('no-such-theme');

    expect($colors['primary'])->toBe(Color::Amber);
});
