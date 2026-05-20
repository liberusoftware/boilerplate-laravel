<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ModularServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge modular config
        $this->mergeConfigFrom(
            config_path('modular.php'), 'modular'
        );

        // Register module manager singleton
        $this->app->singleton(\App\Modules\ModuleManager::class, function ($app) {
            return new \App\Modules\ModuleManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            config_path('modular.php') => config_path('modular.php'),
        ], 'modular-config');

        // Auto-discover and register modules
        if (config('modular.auto_discovery', true)) {
            $this->discoverModules();
        }

        // Register theme support
        if (config('modular.theme_support', true)) {
            $this->registerThemeSupport();
        }

        // Register Filament support
        if (config('modular.filament.enabled', true)) {
            $this->registerFilamentSupport();
        }
    }

    /**
     * Discover and register modules from app-modules directory.
     */
    protected function discoverModules(): void
    {
        $modulesPath = base_path(config('modular.modules_directory', 'app-modules'));
        
        if (!File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $this->registerModule($modulePath);
        }
    }

    /**
     * Register a single module.
     */
    protected function registerModule(string $modulePath): void
    {
        $moduleName = basename($modulePath);
        
        // Register module routes
        $this->registerModuleRoutes($modulePath, $moduleName);
        
        // Register module views
        $this->registerModuleViews($modulePath, $moduleName);
        
        // Register module translations
        $this->registerModuleTranslations($modulePath, $moduleName);
        
        // Register module migrations
        $this->registerModuleMigrations($modulePath, $moduleName);
        
        // Register module service provider if exists
        $this->registerModuleServiceProvider($modulePath, $moduleName);
    }

    /**
     * Register module routes.
     */
    protected function registerModuleRoutes(string $modulePath, string $moduleName): void
    {
        $routesPath = $modulePath . '/routes';
        
        if (!File::exists($routesPath)) {
            return;
        }

        // Register web routes
        if (File::exists($routesPath . '/web.php')) {
            Route::middleware('web')
                ->group($routesPath . '/web.php');
        }

        // Register API routes
        if (File::exists($routesPath . '/api.php')) {
            Route::prefix('api')
                ->middleware('api')
                ->group($routesPath . '/api.php');
        }

        // Register admin routes
        if (File::exists($routesPath . '/admin.php')) {
            Route::prefix('admin')
                ->middleware(['web', 'auth'])
                ->group($routesPath . '/admin.php');
        }
    }

    /**
     * Register module views.
     */
    protected function registerModuleViews(string $modulePath, string $moduleName): void
    {
        $viewsPath = $modulePath . '/resources/views';
        
        if (File::exists($viewsPath)) {
            $this->loadViewsFrom($viewsPath, Str::kebab($moduleName));
        }
    }

    /**
     * Register module translations.
     */
    protected function registerModuleTranslations(string $modulePath, string $moduleName): void
    {
        $langPath = $modulePath . '/resources/lang';
        
        if (File::exists($langPath)) {
            $this->loadTranslationsFrom($langPath, Str::kebab($moduleName));
        }
    }

    /**
     * Register module migrations.
     */
    protected function registerModuleMigrations(string $modulePath, string $moduleName): void
    {
        $migrationsPath = $modulePath . '/database/migrations';
        
        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Register module service provider.
     */
    protected function registerModuleServiceProvider(string $modulePath, string $moduleName): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        $providerClass = "{$namespace}\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
        
        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    /**
     * Register theme support.
     */
    protected function registerThemeSupport(): void
    {
        $themeDirectory = config('modular.theme_directory', 'themes');
        $themesPath = base_path($themeDirectory);
        
        if (File::exists($themesPath)) {
            $this->publishes([
                $themesPath => public_path($themeDirectory),
            ], 'modular-themes');
        }
    }

    /**
     * Register Filament support.
     */
    protected function registerFilamentSupport(): void
    {
        if (!config('modular.filament.enabled', true)) {
            return;
        }

        // Auto-discover Filament resources from modules
        if (config('modular.filament.auto_discover_resources', true)) {
            $this->discoverFilamentResources();
        }
    }

    /**
     * Discover Filament resources from modules.
     */
    protected function discoverFilamentResources(): void
    {
        $modulesPath = base_path(config('modular.modules_directory', 'app-modules'));
        
        if (!File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $filamentPath = $modulePath . '/Filament';
            
            if (File::exists($filamentPath)) {
                // Filament will auto-discover resources through namespace registration
                // This is handled by the module's composer.json autoload configuration
            }
        }
    }
}
