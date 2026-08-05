<?php

use App\Support\ThemeColors;
use Filament\Support\Colors\Color;

it('maps the default theme primary color to the Amber Filament palette', function () {
    config(['theme.default' => 'default']);
    $colors = app(ThemeColors::class)->forSite();

    expect($colors)->toHaveKey('primary');
    expect($colors['primary'])->toBe(Color::Amber);
});

it('maps the dark theme primary color to the Indigo Filament palette', function () {
    config(['theme.default' => 'dark']);
    $colors = app(ThemeColors::class)->forSite();

    expect($colors['primary'])->toBe(Color::Indigo);
});

it('falls back to Amber for an unknown color name', function () {
    // A theme with no colors block resolves to the Amber default.
    config(['theme.default' => 'no-such-theme']);
    $colors = app(ThemeColors::class)->forSite();

    expect($colors['primary'])->toBe(Color::Amber);
});
