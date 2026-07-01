<?php

use App\Services\ThemeManager;
use App\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the persisted site theme', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'dark';
    $settings->save();

    expect(app(ThemeManager::class)->getSiteTheme())->toBe('dark');
});

it('falls back to config default when the site theme is unknown', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'no-such-theme';
    $settings->save();

    expect(app(ThemeManager::class)->getSiteTheme())->toBe(config('theme.default'));
});
