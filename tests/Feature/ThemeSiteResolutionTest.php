<?php

use App\Models\User;
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

it('falls through to the site theme when the user preference names a nonexistent theme', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'dark';
    $settings->save();

    $user = User::factory()->create(['theme_preference' => 'no-such-theme']);

    $this->actingAs($user)->get('/');

    expect(app(ThemeManager::class)->getActiveTheme())->toBe('dark');
});

it('lets a valid user preference win over the site theme', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'default';
    $settings->save();

    $user = User::factory()->create(['theme_preference' => 'dark']);

    $this->actingAs($user)->get('/');

    expect(app(ThemeManager::class)->getActiveTheme())->toBe('dark');
});
