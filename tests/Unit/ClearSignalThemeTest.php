<?php

use App\Services\ThemeManager;
use Filament\Support\Colors\Color;

it('discovers the clear-signal theme', function () {
    $manager = app(ThemeManager::class);

    expect($manager->themeExists('clear-signal'))->toBeTrue();
    expect($manager->getThemes())->toHaveKey('clear-signal');
});

it('labels the clear-signal theme', function () {
    $config = app(ThemeManager::class)->getThemeConfig('clear-signal');

    expect($config['label'] ?? null)->toBe('Clear Signal');
});

it('maps clear-signal primary to the Teal Filament palette', function () {
    expect(app(ThemeManager::class)->getFilamentColors('clear-signal')['primary'])
        ->toBe(Color::Teal);
});
