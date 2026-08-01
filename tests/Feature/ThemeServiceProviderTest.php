<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Liberu\Foundation\Theme\Services\ThemeManager;

it('binds the ThemeManager singleton and the theme alias to one instance', function () {
    expect(app(ThemeManager::class))->toBeInstanceOf(ThemeManager::class)
        ->and(app('theme'))->toBe(app(ThemeManager::class));
});

it('registers the theme blade directives', function () {
    expect(Blade::getCustomDirectives())
        ->toHaveKeys(['themeAsset', 'themeCss', 'themeJs', 'themeLayout']);
});

it('renders the themeAsset directive against the active theme', function () {
    expect(Blade::render("@themeAsset('resources/css/app.css')"))
        ->toContain('themes/'.app(ThemeManager::class)->getActiveTheme().'/resources/css/app.css');
});

it('resolves a shared view name through the active theme inheritance chain', function () {
    app(ThemeManager::class)->setTheme('dark');
    expect(View::getFinder()->find('layouts.app'))
        ->toContain('themes/liberu-base/resources/views/layouts/app.blade.php');

    View::getFinder()->flush();

    app(ThemeManager::class)->setTheme('default');
    expect(View::getFinder()->find('layouts.app'))
        ->toContain('themes/liberu-base/resources/views/layouts/app.blade.php');
});

it('does not throw rendering themeCss/themeJs when the theme asset is not in the Vite manifest', function () {
    app(ThemeManager::class)->setTheme('dark');

    expect(Blade::render('@themeCss @themeJs'))->toBeString();
});

it('persists a theme to session and the authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    app(ThemeManager::class)->persistTheme('dark');

    expect(session('theme_preference'))->toBe('dark')
        ->and($user->fresh()->theme_preference)->toBe('dark');
});

it('persists a theme to session without an authenticated user', function () {
    app(ThemeManager::class)->persistTheme('dark');

    expect(session('theme_preference'))->toBe('dark');
});
