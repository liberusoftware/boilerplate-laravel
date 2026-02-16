<?php

namespace App\Providers;

use App\Services\ThemeManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class, function ($app) {
            return new ThemeManager();
        });

        $this->app->alias(ThemeManager::class, 'theme');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register theme paths
        $themeManager = $this->app->make(ThemeManager::class);
        
        // Set theme from config or user preference
        $theme = $this->determineActiveTheme();
        $themeManager->setTheme($theme);

        // Register Blade directives
        $this->registerBladeDirectives();

        // Share theme data with all views
        View::composer('*', function ($view) use ($themeManager) {
            $view->with('activeTheme', $themeManager->getActiveTheme());
            $view->with('themeConfig', $themeManager->getThemeConfig());
        });
    }

    /**
     * Determine the active theme.
     */
    protected function determineActiveTheme(): string
    {
        // Check if user is authenticated and has a theme preference
        if (auth()->check() && auth()->user()->theme_preference) {
            return auth()->user()->theme_preference;
        }

        // Check session
        if (session()->has('theme_preference')) {
            return session('theme_preference');
        }

        // Fall back to config default
        return config('theme.default', 'default');
    }

    /**
     * Register custom Blade directives for themes.
     */
    protected function registerBladeDirectives(): void
    {
        // @themeAsset('css/custom.css')
        Blade::directive('themeAsset', function ($expression) {
            return "<?php echo asset('themes/' . app('theme')->getActiveTheme() . '/' . {$expression}); ?>";
        });

        // @themeCss
        Blade::directive('themeCss', function () {
            return "<?php 
                \$theme = app('theme')->getActiveTheme();
                if (app('theme')->getThemeCss()) {
                    echo app(\Illuminate\Foundation\Vite::class)('resources/css/themes/' . \$theme . '/app.css');
                }
            ?>";
        });

        // @themeJs
        Blade::directive('themeJs', function () {
            return "<?php 
                \$theme = app('theme')->getActiveTheme();
                if (app('theme')->getThemeJs()) {
                    echo app(\Illuminate\Foundation\Vite::class)('resources/js/themes/' . \$theme . '/app.js');
                }
            ?>";
        });

        // @themeLayout('app')
        Blade::directive('themeLayout', function ($expression) {
            return "<?php echo app('theme')->getLayout({$expression}); ?>";
        });
    }
}
