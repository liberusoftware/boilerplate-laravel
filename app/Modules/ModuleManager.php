<?php

namespace App\Modules;

use Exception;
use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleManager
{
    protected Collection $modules;
    protected array $enabledModules = [];

    public function __construct()
    {
        $this->modules = collect();
        $this->loadModules();
    }

    /**
     * Get all modules.
     */
    public function all(): Collection
    {
        return $this->modules;
    }

    /**
     * Get enabled modules.
     */
    public function enabled(): Collection
    {
        return $this->modules->filter(fn($module) => $module->isEnabled());
    }

    /**
     * Get disabled modules.
     */
    public function disabled(): Collection
    {
        return $this->modules->filter(fn($module) => !$module->isEnabled());
    }

    /**
     * Get a specific module by name.
     */
    public function get(string $name): ?ModuleInterface
    {
        return $this->modules->first(fn($module) => $module->getName() === $name);
    }

    /**
     * Check if a module exists.
     */
    public function has(string $name): bool
    {
        return $this->modules->contains(fn($module) => $module->getName() === $name);
    }

    /**
     * Enable a module.
     */
    public function enable(string $name): bool
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        // Check dependencies
        if (!$this->checkDependencies($module)) {
            throw new \Exception("Module {$name} has unmet dependencies.");
        }

        $module->enable();

        // Persist enabled state
        try {
            $mdl = \App\Models\Module::firstOrNew(['name' => $module->getName()]);
            $mdl->enabled = true;
            $mdl->version = $module->getVersion();
            $mdl->description = $module->getDescription();
            $mdl->dependencies = $module->getDependencies();
            $mdl->config = $module->getConfig();
            $mdl->save();
        } catch (\Throwable $e) {
            \Log::warning("Failed to persist enabled state for module '{$name}': " . $e->getMessage());
        }

        return true;
    }

    /**
     * Disable a module.
     */
    public function disable(string $name): bool
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        // Check if other modules depend on this one
        if ($this->hasDependents($name)) {
            throw new \Exception("Cannot disable module {$name} as other modules depend on it.");
        }

        $module->disable();

        // Persist enabled state
        try {
            $mdl = \App\Models\Module::firstOrNew(['name' => $module->getName()]);
            $mdl->enabled = false;
            $mdl->save();
        } catch (\Throwable $e) {
            \Log::warning("Failed to persist disabled state for module '{$name}': " . $e->getMessage());
        }

        return true;
    }

    /**
     * Install a module.
     */
    public function install(string $name): bool
    {
        $module = $this->get($name);
        
        if (!$module) {
            return false;
        }

        // Check dependencies
        if (!$this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->install();
        return true;
    }

    /**
     * Uninstall a module.
     */
    public function uninstall(string $name): bool
    {
        $module = $this->get($name);
        
        if (!$module) {
            return false;
        }

        // Check if other modules depend on this one
        if ($this->hasDependents($name)) {
            throw new Exception("Cannot uninstall module {$name} as other modules depend on it.");
        }

        $module->uninstall();
        return true;
    }

    /**
     * Register a new module.
     */
    public function register(ModuleInterface $module): void
    {
        $this->modules->put($module->getName(), $module);
    }

    /**
     * Load all modules from the modules directory.
     */
    protected function loadModules(): void
    {
        // Check if caching is enabled
        if (config('modules.cache', true) && !config('modules.development', false)) {
            $cachedModules = Cache::get(config('modules.cache_key', 'app.modules'));
            
            if ($cachedModules) {
                $this->modules = collect($cachedModules);
                return;
            }
        }

        // Load from old modules directory (app/Modules)
        $modulesPath = app_path('Modules');
        if (File::exists($modulesPath)) {
            $modules = File::directories($modulesPath);
            foreach ($modules as $modulePath) {
                $moduleName = basename($modulePath);
                $this->loadModule($moduleName, $modulePath);
            }
        }

        // Load from new modular directory (app-modules)
        $modularPath = base_path(config('modular.modules_directory', 'app-modules'));
        if (File::exists($modularPath)) {
            $modules = File::directories($modularPath);
            foreach ($modules as $modulePath) {
                $moduleName = basename($modulePath);
                $this->loadModularModule($moduleName, $modulePath);
            }
        }

        // Cache the loaded modules
        if (config('modules.cache', true) && !config('modules.development', false)) {
            Cache::put(
                config('modules.cache_key', 'app.modules'),
                $this->modules->all(),
                config('modules.cache_ttl', 3600)
            );
        }
    }

    /**
     * Load a specific module.
     */
    protected function loadModule(string $moduleName, string $modulePath): void
    {
        $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";

        // If class isn't autoloadable, try requiring the module main file directly.
        if (!class_exists($moduleClass)) {
            $mainFile = $modulePath . "/{$moduleName}Module.php";
            if (File::exists($mainFile)) {
                try {
                    require_once $mainFile;
                } catch (\Throwable $e) {
                    \Log::warning("Failed requiring main file for module {$moduleName}: " . $e->getMessage());
                }
            }
        }

        if (class_exists($moduleClass)) {
            try {
                $module = new $moduleClass();
            } catch (\Throwable $e) {
                \Log::warning("Failed instantiating module class {$moduleClass}: " . $e->getMessage());
                return;
            }

            if ($module instanceof ModuleInterface) {
                $this->register($module);

                // Persist module metadata to DB (create or update)
                try {
                    \App\Models\Module::updateOrCreate(
                        ['name' => $module->getName()],
                        [
                            'version' => $module->getVersion(),
                            'description' => $module->getDescription(),
                            'dependencies' => $module->getDependencies(),
                            'config' => $module->getConfig(),
                        ]
                    );
                } catch (\Throwable $e) {
                    \Log::warning("Failed to persist module '{$moduleName}' metadata: " . $e->getMessage());
                }
            }
        } else {
            \Log::warning("Module class {$moduleClass} not found for module path {$modulePath}.");
        }
    }

    /**
     * Load a modular module (internachi/modular pattern).
     */
    protected function loadModularModule(string $moduleName, string $modulePath): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        $moduleClass = "{$namespace}\\{$moduleName}\\{$moduleName}Module";

        if (class_exists($moduleClass)) {
            try {
                // Create a wrapper that implements our ModuleInterface
                $module = new class($moduleClass) implements ModuleInterface {
                    private string $moduleClass;
                    private bool $enabled = false;

                    public function __construct(string $moduleClass)
                    {
                        $this->moduleClass = $moduleClass;
                        // Check if module is enabled from database
                        try {
                            $dbModule = \App\Models\Module::where('name', $moduleClass::getName())->first();
                            $this->enabled = $dbModule ? $dbModule->enabled : false;
                        } catch (\Throwable $e) {
                            // Ignore database errors during module loading
                        }
                    }

                    public function getName(): string
                    {
                        return $this->moduleClass::getName();
                    }

                    public function getVersion(): string
                    {
                        return $this->moduleClass::getVersion();
                    }

                    public function getDescription(): string
                    {
                        return $this->moduleClass::getDescription();
                    }

                    public function getDependencies(): array
                    {
                        return [];
                    }

                    public function isEnabled(): bool
                    {
                        return $this->enabled;
                    }

                    public function enable(): void
                    {
                        $this->enabled = true;
                    }

                    public function disable(): void
                    {
                        $this->enabled = false;
                    }

                    public function install(): void
                    {
                        // Run migrations if needed
                    }

                    public function uninstall(): void
                    {
                        // Rollback migrations if needed
                    }

                    public function getConfig(): array
                    {
                        return config(strtolower($this->getName()), []);
                    }
                };

                $this->register($module);

                // Persist module metadata to DB
                try {
                    \App\Models\Module::updateOrCreate(
                        ['name' => $module->getName()],
                        [
                            'version' => $module->getVersion(),
                            'description' => $module->getDescription(),
                            'dependencies' => $module->getDependencies(),
                            'config' => $module->getConfig(),
                        ]
                    );
                } catch (\Throwable $e) {
                    \Log::warning("Failed to persist modular module '{$moduleName}' metadata: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                \Log::warning("Failed loading modular module '{$moduleName}': " . $e->getMessage());
            }
        }
    }

    /**
     * Check if module dependencies are met.
     */
    protected function checkDependencies(ModuleInterface $module): bool
    {
        foreach ($module->getDependencies() as $dependency) {
            $dependencyModule = $this->get($dependency);
            if (!$dependencyModule || !$dependencyModule->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any modules depend on the given module.
     */
    protected function hasDependents(string $moduleName): bool
    {
        return $this->enabled()->contains(function ($module) use ($moduleName) {
            return in_array($moduleName, $module->getDependencies());
        });
    }

    /**
     * Get module information for display.
     */
    public function getModuleInfo(string $name): array
    {
        $module = $this->get($name);
        
        if (!$module) {
            return [];
        }

        return [
            'name' => $module->getName(),
            'version' => $module->getVersion(),
            'description' => $module->getDescription(),
            'dependencies' => $module->getDependencies(),
            'enabled' => $module->isEnabled(),
            'config' => $module->getConfig(),
        ];
    }

    /**
     * Get all modules information.
     */
    public function getAllModulesInfo(): array
    {
        return $this->modules->map(function ($module) {
            return $this->getModuleInfo($module->getName());
        })->toArray();
    }

    /**
     * Clear the module cache.
     */
    public function clearCache(): void
    {
        Cache::forget(config('modules.cache_key', 'app.modules'));
    }

    /**
     * Check module health status.
     */
    public function checkHealth(string $name): array
    {
        $module = $this->get($name);
        
        if (!$module) {
            return [
                'healthy' => false,
                'errors' => ['Module not found'],
            ];
        }

        $errors = [];
        $warnings = [];

        // Check if module class exists
        $moduleClass = get_class($module);
        if (!class_exists($moduleClass)) {
            $errors[] = "Module class {$moduleClass} not found";
        }

        // Check dependencies
        foreach ($module->getDependencies() as $dependency) {
            $dependencyModule = $this->get($dependency);
            if (!$dependencyModule) {
                $errors[] = "Dependency {$dependency} not found";
            } elseif (!$dependencyModule->isEnabled()) {
                $warnings[] = "Dependency {$dependency} is disabled";
            }
        }

        // Check if module is enabled but has unmet dependencies
        if ($module->isEnabled() && !$this->checkDependencies($module)) {
            $errors[] = "Module is enabled but has unmet dependencies";
        }

        return [
            'healthy' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}