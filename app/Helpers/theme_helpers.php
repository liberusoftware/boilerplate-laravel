<?php

use App\Services\ThemeManager;

if (!function_exists('theme')) {
    /**
     * Get the ThemeManager instance.
     */
    function theme(): ThemeManager
    {
        return app(ThemeManager::class);
    }
}

if (!function_exists('active_theme')) {
    /**
     * Get the active theme name.
     */
    function active_theme(): string
    {
        return theme()->getActiveTheme();
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Generate a URL for a theme asset.
     */
    function theme_asset(string $path, string $theme = null): string
    {
        $theme = $theme ?? active_theme();
        return asset("themes/{$theme}/{$path}");
    }
}

if (!function_exists('theme_path')) {
    /**
     * Get the full path to a theme directory.
     */
    function theme_path(string $theme = null): string
    {
        return theme()->getThemePath($theme);
    }
}

if (!function_exists('theme_views_path')) {
    /**
     * Get the full path to a theme's views directory.
     */
    function theme_views_path(string $theme = null): string
    {
        return theme()->getThemeViewsPath($theme);
    }
}

if (!function_exists('set_theme')) {
    /**
     * Set the active theme.
     */
    function set_theme(string $themeName): void
    {
        theme()->setTheme($themeName);
        
        // Save to session
        session(['theme_preference' => $themeName]);
        
        // Save to user if authenticated
        if (auth()->check()) {
            auth()->user()->update(['theme_preference' => $themeName]);
        }
    }
}

if (!function_exists('theme_layout')) {
    /**
     * Get the theme-specific layout or fall back to default.
     */
    function theme_layout(string $layout): string
    {
        return theme()->getLayout($layout);
    }
}
