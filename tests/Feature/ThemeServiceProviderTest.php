<?php

use App\Models\User;
use App\Services\ThemeManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

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

it('resolves a shared view name to the active theme override and follows switches', function () {
    app(ThemeManager::class)->setTheme('dark');
    expect(View::getFinder()->find('layouts.app'))
        ->toContain('themes/dark/views/layouts/app.blade.php');

    View::getFinder()->flush();

    app(ThemeManager::class)->setTheme('default');
    expect(View::getFinder()->find('layouts.app'))
        ->toContain('themes/default/views/layouts/app.blade.php');
});

it('does not throw rendering themeCss/themeJs when the theme asset is not in the Vite manifest', function () {
    app(ThemeManager::class)->setTheme('dark');

    expect(Blade::render('@themeCss @themeJs'))->toBeString();
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
