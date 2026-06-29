<?php

use App\Models\User;
use App\Services\ThemeManager;
use Illuminate\Support\Facades\Blade;

it('binds the ThemeManager singleton and the theme alias to one instance', function () {
    expect(app(ThemeManager::class))->toBeInstanceOf(ThemeManager::class)
        ->and(app('theme'))->toBe(app(ThemeManager::class));
});

it('registers the theme blade directives', function () {
    expect(Blade::getCustomDirectives())
        ->toHaveKeys(['themeAsset', 'themeCss', 'themeJs', 'themeLayout']);
});

it('renders the themeAsset directive against the active theme', function () {
    expect(Blade::render("@themeAsset('css/app.css')"))
        ->toContain('themes/'.active_theme().'/css/app.css');
});

it('persists set_theme to session and the authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    set_theme('dark');

    expect(session('theme_preference'))->toBe('dark')
        ->and($user->fresh()->theme_preference)->toBe('dark');
});

it('set_theme without auth writes session only and does not throw', function () {
    set_theme('dark');

    expect(session('theme_preference'))->toBe('dark');
});
