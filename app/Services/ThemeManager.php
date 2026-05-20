<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

class ThemeManager
{
    protected string $activeTheme;
    protected array $themes = [];
    protected string $themesPath;

    public function __construct()
    {
        $this->themesPath = base_path('themes');
        $this->activeTheme = config('theme.default', 'default');
        $this->loadThemes();
    }

    /**
     * Load all available themes.
     */
    protected function loadThemes(): void
    {
        if (!File::exists($this->themesPath)) {
            File::makeDirectory($this->themesPath, 0755, true);
        }

        $themeDirs = File::directories($this->themesPath);

        foreach ($themeDirs as $themeDir) {
            $themeName = basename($themeDir);
            $themeConfigPath = $themeDir . '/theme.json';

            if (File::exists($themeConfigPath)) {
                $themeConfig = json_decode(File::get($themeConfigPath), true);
                $this->themes[$themeName] = $themeConfig;
            } else {
                $this->themes[$themeName] = [
                    'name' => $themeName,
                    'label' => ucfirst($themeName),
                    'description' => "Custom theme: {$themeName}",
                ];
            }
        }
    }

    /**
     * Set the active theme.
     */
    public function setTheme(string $theme): void
    {
        if ($this->themeExists($theme)) {
            $this->activeTheme = $theme;
            $this->registerThemePaths();
        }
    }

    /**
     * Get the active theme.
     */
    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }

    /**
     * Get all available themes.
     */
    public function getThemes(): array
    {
        return $this->themes;
    }

    /**
     * Check if a theme exists.
     */
    public function themeExists(string $theme): bool
    {
        return isset($this->themes[$theme]);
    }

    /**
     * Get theme path.
     */
    public function getThemePath(string $theme = null): string
    {
        $theme = $theme ?? $this->activeTheme;
        return $this->themesPath . '/' . $theme;
    }

    /**
     * Get theme views path.
     */
    public function getThemeViewsPath(string $theme = null): string
    {
        $theme = $theme ?? $this->activeTheme;
        return $this->themesPath . '/' . $theme . '/views';
    }

    /**
     * Get theme asset path (CSS/JS).
     */
    public function getThemeAssetPath(string $type, string $theme = null): ?string
    {
        $theme = $theme ?? $this->activeTheme;
        $assetPath = $this->themesPath . '/' . $theme . '/' . $type;

        if (File::exists($assetPath)) {
            return $assetPath;
        }

        return null;
    }

    /**
     * Register theme view paths with Laravel's view finder.
     */
    public function registerThemePaths(): void
    {
        $themeViewsPath = $this->getThemeViewsPath();

        if (File::exists($themeViewsPath)) {
            // Add theme views path before the default views path
            View::getFinder()->prependLocation($themeViewsPath);
        }
    }

    /**
     * Get theme CSS file path for Vite.
     */
    public function getThemeCss(string $theme = null): ?string
    {
        $theme = $theme ?? $this->activeTheme;
        $cssPath = "themes/{$theme}/css/app.css";

        if (File::exists(base_path($cssPath))) {
            return $cssPath;
        }

        return null;
    }

    /**
     * Get theme JS file path for Vite.
     */
    public function getThemeJs(string $theme = null): ?string
    {
        $theme = $theme ?? $this->activeTheme;
        $jsPath = "themes/{$theme}/js/app.js";

        if (File::exists(base_path($jsPath))) {
            return $jsPath;
        }

        return null;
    }

    /**
     * Get theme configuration.
     */
    public function getThemeConfig(string $theme = null): array
    {
        $theme = $theme ?? $this->activeTheme;
        return $this->themes[$theme] ?? [];
    }

    /**
     * Clear theme cache.
     */
    public function clearCache(): void
    {
        Cache::forget('themes.active');
        Cache::forget('themes.list');
    }

    /**
     * Check if theme has custom layout.
     */
    public function hasCustomLayout(string $layout, string $theme = null): bool
    {
        $theme = $theme ?? $this->activeTheme;
        $layoutPath = $this->getThemeViewsPath($theme) . "/layouts/{$layout}.blade.php";

        return File::exists($layoutPath);
    }

    /**
     * Get the theme layout path or fall back to default.
     */
    public function getLayout(string $layout, string $theme = null): string
    {
        $theme = $theme ?? $this->activeTheme;

        if ($this->hasCustomLayout($layout, $theme)) {
            return "layouts.{$layout}";
        }

        return "layouts.{$layout}";
    }
}
