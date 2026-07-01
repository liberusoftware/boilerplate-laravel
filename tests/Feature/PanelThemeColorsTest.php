<?php

use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Services\ThemeManager;
use App\Settings\SiteSettings;
use Filament\Panel;
use Filament\Support\Colors\Color;

function setSiteTheme(string $theme): void
{
    $settings = app(SiteSettings::class);
    $settings->active_theme = $theme;
    $settings->save();

    // getSiteTheme() reads SiteSettings via the container; drop the cached
    // ThemeManager instance so it re-resolves the (now-persisted) setting.
    app()->forgetInstance(ThemeManager::class);
}

it('admin panel primary color follows the dark site theme', function () {
    setSiteTheme('dark');

    $colors = (new AdminPanelProvider(app()))->panel(Panel::make())->getColors();

    expect($colors['primary'])->toBe(Color::Indigo);
});

it('admin panel primary color follows the default site theme', function () {
    setSiteTheme('default');

    $colors = (new AdminPanelProvider(app()))->panel(Panel::make())->getColors();

    expect($colors['primary'])->toBe(Color::Amber);
});

it('app panel primary color follows the dark site theme', function () {
    setSiteTheme('dark');

    $colors = (new AppPanelProvider(app()))->panel(Panel::make())->getColors();

    expect($colors['primary'])->toBe(Color::Indigo);
});
