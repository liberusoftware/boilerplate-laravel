<?php

namespace App\Services;

use App\Settings\SiteSettings;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;
use Throwable;

class ThemeManager
{
    protected string $activeTheme;

    /** @var array<string, array<string, mixed>> */
    protected array $themes = [];

    protected readonly string $themesPath;

    public function __construct()
    {
        $this->themesPath = base_path('themes');
        $default = config('theme.default', 'default');
        $this->activeTheme = is_string($default) ? $default : 'default';
        $this->loadThemes();
    }

    /**
     * Load all available themes.
     */
    protected function loadThemes(): void
    {
        if (! File::exists($this->themesPath)) {
            File::makeDirectory($this->themesPath, 0755, true);
        }

        $themeDirs = File::directories($this->themesPath);

        foreach ($themeDirs as $themeDir) {
            $themeName = basename($themeDir);
            $themeConfigPath = $themeDir.'/theme.json';

            $decoded = File::exists($themeConfigPath)
                ? json_decode(File::get($themeConfigPath), true)
                : null;

            $this->themes[$themeName] = is_array($decoded) ? $decoded : [
                'name' => $themeName,
                'label' => ucfirst($themeName),
                'description' => "Custom theme: {$themeName}",
            ];
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
     * The admin-selected site-wide theme, or the config default when the
     * setting is unavailable or names a theme that does not exist. Never throws.
     */
    public function getSiteTheme(): string
    {
        $default = config('theme.default', 'default');
        $default = is_string($default) ? $default : 'default';

        try {
            $theme = app(SiteSettings::class)->active_theme;
        } catch (Throwable) {
            return $default;
        }

        return $this->themeExists($theme) ? $theme : $default;
    }

    /**
     * Get all available themes.
     *
     * @return array<string, array<string, mixed>>
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
    public function getThemePath(?string $theme = null): string
    {
        $theme = $theme ?? $this->activeTheme;

        return $this->themesPath.'/'.$theme;
    }

    /**
     * Get theme views path.
     */
    public function getThemeViewsPath(?string $theme = null): string
    {
        $theme = $theme ?? $this->activeTheme;

        return $this->themesPath.'/'.$theme.'/views';
    }

    /**
     * Get theme asset path (CSS/JS).
     */
    public function getThemeAssetPath(string $type, ?string $theme = null): ?string
    {
        $theme = $theme ?? $this->activeTheme;
        $assetPath = $this->themesPath.'/'.$theme.'/'.$type;

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
        $finder = View::getFinder();

        if (! File::exists($themeViewsPath) || ! $finder instanceof FileViewFinder) {
            return;
        }

        $resolvedPath = realpath($themeViewsPath) ?: $themeViewsPath;
        $paths = $finder->getPaths();

        if (($paths[0] ?? null) === $resolvedPath) {
            // Already registered and already has top priority: prependLocation() has no
            // dedupe, and this runs per view render (via ThemeServiceProvider's
            // View::composer), so re-adding it every render would grow the finder's
            // paths array unbounded under Octane.
            return;
        }

        // Drop any stale registration of this theme's path before re-prepending, so
        // switching themes mid-worker (a different theme was prepended after this one)
        // still gives the newly active theme priority instead of leaving it shadowed.
        // Prepend the realpath (the same value used for the dedupe check above) so a
        // symlinked deploy path (e.g. Octane atomic releases) stays idempotent — a raw
        // path here would never match $resolvedPath and would accumulate every render.
        $finder->setPaths(array_values(array_diff($paths, [$resolvedPath])));
        $finder->prependLocation($resolvedPath);
    }

    /**
     * Get theme CSS file path for Vite.
     */
    public function getThemeCss(?string $theme = null): ?string
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
    public function getThemeJs(?string $theme = null): ?string
    {
        $theme = $theme ?? $this->activeTheme;
        $jsPath = "themes/{$theme}/js/app.js";

        if (File::exists(base_path($jsPath))) {
            return $jsPath;
        }

        return null;
    }

    /**
     * Whether the given path is present in the built Vite manifest. Used to gate
     * the @themeCss/@themeJs directives: a theme asset on disk but not yet added to
     * the Vite build would otherwise throw "Unable to locate file in Vite manifest".
     */
    public function viteHasAsset(string $path): bool
    {
        $manifest = public_path('build/manifest.json');

        if (! File::exists($manifest)) {
            return false;
        }

        $decoded = json_decode(File::get($manifest), true);

        return is_array($decoded) && array_key_exists($path, $decoded);
    }

    /**
     * Get theme configuration.
     *
     * @return array<string, mixed>
     */
    public function getThemeConfig(?string $theme = null): array
    {
        $theme = $theme ?? $this->activeTheme;

        return $this->themes[$theme] ?? [];
    }

    /**
     * Whitelist of Tailwind color names → Filament Color palettes. Explicit map
     * (not dynamic constant lookup) so an unexpected theme.json value can never
     * reference an undefined constant.
     *
     * @return array<string, array<int, string>>
     */
    protected function filamentColorMap(): array
    {
        return [
            'slate' => Color::Slate,
            'gray' => Color::Gray,
            'zinc' => Color::Zinc,
            'neutral' => Color::Neutral,
            'stone' => Color::Stone,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'yellow' => Color::Yellow,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'emerald' => Color::Emerald,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet' => Color::Violet,
            'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'pink' => Color::Pink,
            'rose' => Color::Rose,
        ];
    }

    /**
     * Build the Filament panel color palette for a theme from its theme.json
     * `colors.primary`. Unknown or missing → Amber (the shipped default look).
     *
     * @return array<string, array<int, string>>
     */
    public function getFilamentColors(?string $theme = null): array
    {
        $config = $this->getThemeConfig($theme);
        $colors = is_array($config['colors'] ?? null) ? $config['colors'] : [];
        $primary = is_string($colors['primary'] ?? null) ? strtolower($colors['primary']) : 'amber';

        $map = $this->filamentColorMap();

        return ['primary' => $map[$primary] ?? Color::Amber];
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
    public function hasCustomLayout(string $layout, ?string $theme = null): bool
    {
        $theme = $theme ?? $this->activeTheme;
        $layoutPath = $this->getThemeViewsPath($theme)."/layouts/{$layout}.blade.php";

        return File::exists($layoutPath);
    }

    /**
     * Get the theme layout path. Theme view paths are registered with the view
     * finder, so the same "layouts.{name}" reference resolves to the theme's
     * override when present and the default otherwise.
     */
    public function getLayout(string $layout, ?string $theme = null): string
    {
        return "layouts.{$layout}";
    }
}
