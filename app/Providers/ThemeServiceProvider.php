<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ThemeManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class, fn () => new ThemeManager());
        $this->app->alias(ThemeManager::class, 'theme');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $themeManager = $this->app->make(ThemeManager::class);
        $themeManager->setTheme($this->determineActiveTheme());

        $this->registerBladeDirectives();

        View::composer('*', function (ViewContract $view) use ($themeManager): void {
            // ponytail: re-derive per view render, not just once at boot. ThemeManager
            // is a singleton resolved once per app lifecycle (long-lived under Octane;
            // reused across a whole test method under Pest) — without this, an
            // admin-changed site theme (or session/user pref set mid-lifecycle) would
            // never be picked up until the process restarts. getSiteTheme() is a cheap
            // in-memory settings read, so re-running this per view is negligible.
            $themeManager->setTheme($this->determineActiveTheme());

            $view->with('activeTheme', $themeManager->getActiveTheme());
            $view->with('themeConfig', $themeManager->getThemeConfig());
        });
    }

    /**
     * Determine the active theme: authenticated user preference → session → site theme → config default.
     */
    protected function determineActiveTheme(): string
    {
        $themeManager = $this->app->make(ThemeManager::class);

        $user = auth()->user();
        if ($user instanceof User && is_string($user->theme_preference) && $user->theme_preference !== '' && $themeManager->themeExists($user->theme_preference)) {
            return $user->theme_preference;
        }

        $session = session('theme_preference');
        if (is_string($session) && $session !== '' && $themeManager->themeExists($session)) {
            return $session;
        }

        // Admin-selected site-wide theme (validated; safe fallback to config default).
        return $themeManager->getSiteTheme();
    }

    /**
     * Register custom Blade directives for themes.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('themeAsset', fn (string $expression): string => "<?php echo asset('themes/' . app('theme')->getActiveTheme() . '/' . {$expression}); ?>");

        // ponytail: @themeCss/@themeJs gate on the Vite MANIFEST, not disk — per-theme
        // Vite inputs are deferred, so until themes/*/{css,js} are added to vite.config.js
        // input + built, these emit nothing rather than throwing "Unable to locate file
        // in Vite manifest". They light up automatically once the assets are built.
        Blade::directive('themeCss', fn (): string => "<?php \$__p = 'themes/' . app('theme')->getActiveTheme() . '/css/app.css'; if (app('theme')->viteHasAsset(\$__p)) { echo app(\Illuminate\Foundation\Vite::class)(\$__p); } ?>");

        Blade::directive('themeJs', fn (): string => "<?php \$__p = 'themes/' . app('theme')->getActiveTheme() . '/js/app.js'; if (app('theme')->viteHasAsset(\$__p)) { echo app(\Illuminate\Foundation\Vite::class)(\$__p); } ?>");

        Blade::directive('themeLayout', fn (string $expression): string => "<?php echo app('theme')->getLayout({$expression}); ?>");

        // Load the active theme's built CSS bundle (or app.css fallback) + main JS.
        Blade::directive('themeVite', fn (): string => "<?php echo app(\Illuminate\Foundation\Vite::class)([app('theme')->activeCssEntry(), 'resources/js/app.js']); ?>");
    }
}
