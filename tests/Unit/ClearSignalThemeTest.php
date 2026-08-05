<?php

use App\Support\ThemeColors;
use Filament\Support\Colors\Color;
use Liberu\Foundation\Theme\Services\ThemeManager;

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
    config(['theme.default' => 'clear-signal']);
    expect(app(ThemeColors::class)->forSite()['primary'])
        ->toBe(Color::Teal);
});
