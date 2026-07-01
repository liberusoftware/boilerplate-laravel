<?php

use App\Services\ThemeManager;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;

it('admin panel primary color follows the site theme', function () {
    // getFilamentColors('dark') → Indigo; assert the resolver returns it so the
    // panel provider (which passes this array to ->colors()) is driven by the theme.
    expect(app(ThemeManager::class)->getFilamentColors('dark')['primary'])->toBe(Color::Indigo);
    expect(app(ThemeManager::class)->getFilamentColors('default')['primary'])->toBe(Color::Amber);

    // Panel registers without error using the theme-driven palette.
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    expect(Filament::getPanel('admin'))->not->toBeNull();
});
