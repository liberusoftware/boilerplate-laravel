<?php

use App\Services\ThemeManager;
use App\Settings\SiteSettings;

it('uses the site theme when no user or session preference is set', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'dark';
    $settings->save();

    // Re-boot the provider path by resolving a fresh ThemeManager via a request.
    $this->get('/');

    expect(app(ThemeManager::class)->getActiveTheme())->toBe('dark');
});

it('lets a session preference win over the site theme', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'dark';
    $settings->save();

    session(['theme_preference' => 'default']);
    $this->get('/');

    expect(app(ThemeManager::class)->getActiveTheme())->toBe('default');
});
