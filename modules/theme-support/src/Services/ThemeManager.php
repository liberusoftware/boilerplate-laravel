<?php

namespace Liberu\Foundation\Theme\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\Settings\Settings\SiteSettings;
use Liberu\Foundation\Theme\Cache\ThemeCache;
use Liberu\Foundation\Theme\Discovery\ThemeDiscovery;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\Theme\Manifests\ThemeManifest;
use Throwable;

final class ThemeManager
{
    protected string $activeTheme;

    /** @var array<string, ThemeManifest> */
    protected array $themes = [];

    protected readonly string $themesPath;

    public function __construct(?string $themesPath = null)
    {
        $this->themesPath = $themesPath ?? base_path('themes');
        $this->loadThemes();
        $fallback = (string) config('theme.fallback', config('theme.default', 'default'));

        if (! isset($this->themes[$fallback])) {
            throw new InvalidTheme("Safe fallback theme [{$fallback}] is not installed.");
        }

        $this->activeTheme = $fallback;
    }

    protected function loadThemes(): void
    {
        $cachePath = (string) config('theme.cache_path', base_path('bootstrap/cache/liberu-themes.php.cache'));
        if ((bool) config('theme.cache', false)) {
            if (! File::isFile($cachePath)) {
                throw new InvalidTheme('Theme caching is enabled but no deployment cache exists.');
            }
            $this->themes = (new ThemeCache())->load($cachePath);
        } else {
            $this->themes = (new ThemeDiscovery())->discover($this->themesPath);
        }
        foreach (array_keys($this->themes) as $name) {
            $this->inheritanceChain($name);
        }
    }

    public function setTheme(string $theme): void
    {
        if (! $this->themeIsCompatible($theme)) {
            Log::warning('Theme selection fell back safely.', ['requested_theme' => $theme, 'fallback' => config('theme.fallback', 'default')]);
            $theme = (string) config('theme.fallback', 'default');
        }
        $this->activeTheme = $theme;
        $this->registerThemePaths();
    }

    public function persistTheme(string $theme): void
    {
        $this->setTheme($theme);
        session(['theme_preference' => $this->activeTheme]);

        $user = auth()->user();
        if ($user instanceof Model) {
            $user->update(['theme_preference' => $this->activeTheme]);
        }
    }

    public function selectForSurface(string $surface, ?string $site = null, ?string $tenant = null): string
    {
        $selected = config("theme.tenants.{$tenant}.{$surface}")
            ?? config("theme.sites.{$site}.{$surface}")
            ?? config("theme.surfaces.{$surface}")
            ?? config('theme.default', 'default');
        $this->setTheme(is_string($selected) ? $selected : 'default');

        return $this->activeTheme;
    }

    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }

    public function getSiteTheme(): string
    {
        $fallback = (string) config('theme.default', 'default');
        try {
            $theme = app(SiteSettings::class)->active_theme;
        } catch (Throwable) {
            return $fallback;
        }

        return $this->themeIsCompatible($theme) ? $theme : $fallback;
    }

    public function getThemes(): array
    {
        return array_map(fn (ThemeManifest $manifest) => $manifest->toArray(), $this->themes);
    }

    public function providers(): array
    {
        return array_values(array_unique(array_map(fn (ThemeManifest $manifest) => $manifest->provider(), $this->themes)));
    }

    public function themeExists(string $theme): bool
    {
        return isset($this->themes[$theme]);
    }

    public function themeIsCompatible(string $theme): bool
    {
        if (! isset($this->themes[$theme])) {
            return false;
        }
        $available = $this->enabledCapabilities();

        return array_diff($this->themes[$theme]->requiredCapabilities(), $available) === [];
    }

    /**
     * A theme is wherever it was discovered: a Composer package installed outside
     * the tracked tree is on disk somewhere the tracked path cannot name.
     */
    public function getThemePath(?string $theme = null): string
    {
        $name = $theme ?? $this->activeTheme;

        return $this->themes[$name]->path ?? $this->themesPath.'/'.$name;
    }

    public function getThemeViewsPath(?string $theme = null): string
    {
        return $this->getThemePath($theme).'/resources/views';
    }

    public function getThemeAssetPath(string $type, ?string $theme = null): ?string
    {
        $path = $this->getThemePath($theme).'/resources/'.$type;

        return File::exists($path) ? $path : null;
    }

    public function inheritanceChain(?string $theme = null): array
    {
        $name = $theme ?? $this->activeTheme;
        $chain = [];
        $seen = [];
        while ($name !== null) {
            if (isset($seen[$name])) {
                throw new InvalidTheme("Theme inheritance cycle involving [{$name}].");
            }$manifest = $this->themes[$name] ?? throw new InvalidTheme("Missing parent theme [{$name}].");
            $seen[$name] = true;
            $chain[] = $name;
            $name = $manifest->parent();
        }

        return $chain;
    }

    public function registerThemePaths(): void
    {
        $finder = View::getFinder();
        if (! $finder instanceof FileViewFinder) {
            return;
        }
        $themeRoots = array_map(fn (string $name) => realpath($this->getThemeViewsPath($name)) ?: $this->getThemeViewsPath($name), array_keys($this->themes));
        $paths = array_values(array_diff($finder->getPaths(), $themeRoots));
        $chain = array_reverse($this->inheritanceChain());
        foreach ($chain as $name) {
            $path = realpath($this->getThemeViewsPath($name)) ?: $this->getThemeViewsPath($name);
            if (File::isDirectory($path)) {
                array_unshift($paths, $path);
            }
        }
        $finder->setPaths(array_values(array_unique($paths)));
    }

    public function getThemeCss(?string $theme = null): ?string
    {
        return $this->firstAsset('css', $theme);
    }

    public function getThemeJs(?string $theme = null): ?string
    {
        return $this->firstAsset('js', $theme);
    }

    public function activeEntries(): array
    {
        return array_values(array_unique(array_filter([$this->activeCssEntry(), $this->getThemeJs(), 'resources/js/app.js'])));
    }

    public function assetUrl(string $path, ?string $theme = null): string
    {
        if (str_starts_with($path, '/') || str_contains($path, '..')) {
            throw new InvalidTheme('Theme asset path is unsafe.');
        }

        return asset('themes/'.($theme ?? $this->activeTheme).'/'.$path);
    }

    private function firstAsset(string $kind, ?string $theme): ?string
    {
        $name = $theme ?? $this->activeTheme;
        foreach ($this->inheritanceChain($name) as $candidate) {
            foreach ($this->themes[$candidate]->assets($kind) as $asset) {
                $path = "themes/{$candidate}/{$asset}";
                if (File::isFile(base_path($path))) {
                    return $path;
                }
            }
        }

        return null;
    }

    public function activeCssEntry(): string
    {
        $css = $this->getThemeCss();

        return $css && $this->viteHasAsset($css) ? $css : 'resources/css/app.css';
    }

    public function viteHasAsset(string $path): bool
    {
        $manifest = public_path('build/manifest.json');
        if (! File::isFile($manifest)) {
            return false;
        }$decoded = json_decode(File::get($manifest), true);

        return is_array($decoded) && array_key_exists($path, $decoded);
    }

    public function getThemeConfig(?string $theme = null): array
    {
        return isset($this->themes[$theme ?? $this->activeTheme]) ? $this->themes[$theme ?? $this->activeTheme]->toArray() : [];
    }

    public function primaryColor(?string $theme = null): string
    {
        $config = $this->getThemeConfig($theme);

        return strtolower((string) ($config['colors']['primary'] ?? 'amber'));
    }

    public function clearCache(): void
    {
        (new ThemeCache())->clear((string) config(
            'theme.cache_path',
            base_path('bootstrap/cache/liberu-themes.php.cache'),
        ));
    }

    public function hasCustomLayout(string $layout, ?string $theme = null): bool
    {
        foreach ($this->inheritanceChain($theme) as $candidate) {
            if (File::isFile($this->getThemeViewsPath($candidate)."/layouts/{$layout}.blade.php")) {
                return true;
            }
        }

        return false;
    }

    public function getLayout(string $layout, ?string $theme = null): string
    {
        return "layouts.{$layout}";
    }

    private function enabledCapabilities(): array
    {
        if (! app()->bound(ModuleRegistry::class)) {
            return [];
        }$resolved = app(ModuleRegistry::class)->resolve((array) config('modules.enabled', []), (array) config('modules.disabled', []));

        return array_values(array_unique(array_merge(...array_map(fn ($manifest) => $manifest->capabilities(), $resolved))));
    }
}
