<?php

namespace App\Modules;

use App\Models\Module;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerModules();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootModules();
    }

    /**
     * Register all modules found in the modules directory.
     */
    protected function registerModules(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $this->registerModule($moduleName, $modulePath);
        }
    }

    /**
     * Register a specific module.
     */
    protected function registerModule(string $moduleName, string $modulePath): void
    {
        // Check if module is enabled before loading routes/views
        $isEnabled = $this->isModuleEnabled($moduleName);

        // Register module service provider if it exists
        $providerPath = $modulePath.'/Providers/'.$moduleName.'ServiceProvider.php';
        if (File::exists($providerPath)) {
            $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }

        // Register module configuration (always load configuration)
        $configPath = $modulePath.'/config';
        if (File::exists($configPath)) {
            $configFiles = File::files($configPath);
            foreach ($configFiles as $configFile) {
                $configName = Str::snake($moduleName).'.'.$configFile->getFilenameWithoutExtension();
                $this->mergeConfigFrom($configFile->getPathname(), $configName);
            }
        }

        // Only register routes and views for enabled modules
        if ($isEnabled) {
            // Register module routes
            $this->registerModuleRoutes($moduleName, $modulePath);

            // Register module views
            $viewsPath = $modulePath.'/resources/views';
            if (File::exists($viewsPath)) {
                $this->loadViewsFrom($viewsPath, Str::snake($moduleName));
            }

            // Register module translations
            $langPath = $modulePath.'/resources/lang';
            if (File::exists($langPath)) {
                $this->loadTranslationsFrom($langPath, Str::snake($moduleName));
            }
        }

        // Register module migrations (always available for artisan commands)
        $migrationsPath = $modulePath.'/database/migrations';
        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Register module routes.
     */
    protected function registerModuleRoutes(string $moduleName, string $modulePath): void
    {
        $routesPath = $modulePath.'/routes';

        if (! File::exists($routesPath)) {
            return;
        }

        // Web routes
        $webRoutesPath = $routesPath.'/web.php';
        if (File::exists($webRoutesPath)) {
            $this->loadRoutesFrom($webRoutesPath);
        }

        // API routes
        $apiRoutesPath = $routesPath.'/api.php';
        if (File::exists($apiRoutesPath)) {
            $this->loadRoutesFrom($apiRoutesPath);
        }

        // Admin routes (for Filament integration)
        $adminRoutesPath = $routesPath.'/admin.php';
        if (File::exists($adminRoutesPath)) {
            $this->loadRoutesFrom($adminRoutesPath);
        }
    }

    /**
     * Boot all registered modules.
     */
    protected function bootModules(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $this->bootModule($moduleName, $modulePath);
        }
    }

    /**
     * Boot a specific module.
     */
    protected function bootModule(string $moduleName, string $modulePath): void
    {
        // Publish module assets
        $assetsPath = $modulePath.'/resources/assets';
        if (File::exists($assetsPath)) {
            $this->publishes([
                $assetsPath => public_path("modules/{$moduleName}"),
            ], Str::snake($moduleName).'-assets');
        }

        // Publish module configuration
        $configPath = $modulePath.'/config';
        if (File::exists($configPath)) {
            $configFiles = File::files($configPath);
            foreach ($configFiles as $configFile) {
                $this->publishes([
                    $configFile->getPathname() => config_path(Str::snake($moduleName).'.'.$configFile->getFilename()),
                ], Str::snake($moduleName).'-config');
            }
        }
    }

    /**
     * Check if a module is enabled.
     */
    protected function isModuleEnabled(string $moduleName): bool
    {
        try {
            // Check database for module enabled status
            $record = Module::where('name', $moduleName)->first();
            if ($record !== null) {
                return (bool) $record->enabled;
            }
        } catch (\Throwable $e) {
            // Database may not be set up yet, or Module table doesn't exist
            // In this case, we default to loading the module
        }

        // Default to enabled if no database record exists
        return true;
    }
}
