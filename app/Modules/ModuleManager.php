<?php

namespace App\Modules;

use App\Models\Module;
use App\Modules\Contracts\ModuleInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ModuleManager
{
    /**
     * @var Collection<string, ModuleInterface>
     */
    protected Collection $modules;

    public function __construct()
    {
        $this->modules = new Collection();
        $this->loadModules();
    }

    /**
     * Get all modules.
     *
     * @return Collection<string, ModuleInterface>
     */
    public function all(): Collection
    {
        return $this->modules;
    }

    /**
     * Get enabled modules.
     *
     * @return Collection<string, ModuleInterface>
     */
    public function enabled(): Collection
    {
        return $this->modules->filter(fn (ModuleInterface $module) => $module->isEnabled());
    }

    /**
     * Get disabled modules.
     *
     * @return Collection<string, ModuleInterface>
     */
    public function disabled(): Collection
    {
        return $this->modules->filter(fn (ModuleInterface $module) => ! $module->isEnabled());
    }

    /**
     * Get a specific module by name.
     */
    public function get(string $name): ?ModuleInterface
    {
        return $this->modules->first(fn (ModuleInterface $module) => $module->getName() === $name);
    }

    /**
     * Find a module by name (alias for get).
     */
    public function find(string $name): ?ModuleInterface
    {
        return $this->get($name);
    }

    /**
     * Check if a module exists.
     */
    public function has(string $name): bool
    {
        return $this->modules->contains(fn (ModuleInterface $module) => $module->getName() === $name);
    }

    /**
     * Enable a module.
     */
    public function enable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module) {
            return false;
        }

        // Check dependencies
        if (! $this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->enable();

        // Persist enabled state
        try {
            $mdl = Module::firstOrNew(['name' => $module->getName()]);
            $mdl->enabled = true;
            $mdl->version = $module->getVersion();
            $mdl->description = $module->getDescription();
            $mdl->dependencies = $module->getDependencies();
            $mdl->config = $module->getConfig();
            $mdl->save();
        } catch (\Throwable $e) {
            Log::warning("Failed to persist enabled state for module '{$name}': ".$e->getMessage());
        }

        return true;
    }

    /**
     * Disable a module.
     */
    public function disable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module) {
            return false;
        }

        // Check if other modules depend on this one
        if ($this->hasDependents($name)) {
            throw new Exception("Cannot disable module {$name} as other modules depend on it.");
        }

        $module->disable();

        // Persist enabled state
        try {
            $mdl = Module::firstOrNew(['name' => $module->getName()]);
            $mdl->enabled = false;
            $mdl->save();
        } catch (\Throwable $e) {
            Log::warning("Failed to persist disabled state for module '{$name}': ".$e->getMessage());
        }

        return true;
    }

    /**
     * Install a module.
     */
    public function install(string $name): bool
    {
        $module = $this->get($name);

        if (! $module) {
            return false;
        }

        // Check dependencies
        if (! $this->checkDependencies($module)) {
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

        if (! $module) {
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
        $cacheKey = config('modules.cache_key', 'app.modules');
        $cacheKey = is_string($cacheKey) ? $cacheKey : 'app.modules';

        // Check if caching is enabled
        if (config('modules.cache', true) && ! config('modules.development', false)) {
            $cachedModules = Cache::get($cacheKey);

            if (is_array($cachedModules)) {
                /** @var Collection<string, ModuleInterface> $restored */
                $restored = new Collection($cachedModules);
                $this->modules = $restored;

                return;
            }
        }

        // Load from the modules directory (app/Modules)
        $modulesPath = app_path('Modules');
        if (File::exists($modulesPath)) {
            $modules = File::directories($modulesPath);
            foreach ($modules as $modulePath) {
                $moduleName = basename($modulePath);
                $this->loadModule($moduleName, $modulePath);
            }
        }

        // Cache the loaded modules
        if (config('modules.cache', true) && ! config('modules.development', false)) {
            $ttl = config('modules.cache_ttl', 3600);
            Cache::put(
                $cacheKey,
                $this->modules->all(),
                is_int($ttl) ? $ttl : 3600
            );
        }
    }

    /**
     * Load a specific module.
     */
    protected function loadModule(string $moduleName, string $modulePath): void
    {
        // Only directories that declare a module.json are modules — skip the framework
        // subfolders (Contracts/, Events/, Traits/, Support/).
        if (! File::exists($modulePath.'/module.json')) {
            return;
        }

        // Support both layouts: app/Modules/Foo/FooModule.php and app/Modules/Foo/Foo.php.
        $candidates = [
            "App\\Modules\\{$moduleName}\\{$moduleName}Module",
            "App\\Modules\\{$moduleName}\\{$moduleName}",
        ];

        $moduleClass = $this->resolveModuleClass($candidates);

        // If not autoloadable, require the main file directly, then re-check.
        if ($moduleClass === null) {
            foreach (["{$moduleName}Module.php", "{$moduleName}.php"] as $file) {
                $mainFile = $modulePath.'/'.$file;
                if (File::exists($mainFile)) {
                    try {
                        require_once $mainFile;
                    } catch (\Throwable $e) {
                        Log::warning("Failed requiring main file for module {$moduleName}: ".$e->getMessage());
                    }
                }
            }
            $moduleClass = $this->resolveModuleClass($candidates);
        }

        if ($moduleClass === null) {
            Log::warning("Module class not found for module path {$modulePath}.");

            return;
        }

        try {
            $module = new $moduleClass();
        } catch (\Throwable $e) {
            Log::warning("Failed instantiating module class {$moduleClass}: ".$e->getMessage());

            return;
        }

        if (! $module instanceof ModuleInterface) {
            return;
        }

        $this->register($module);

        // Persist module metadata to DB (create or update).
        try {
            Module::updateOrCreate(
                ['name' => $module->getName()],
                [
                    'version' => $module->getVersion(),
                    'description' => $module->getDescription(),
                    'dependencies' => $module->getDependencies(),
                    'config' => $module->getConfig(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning("Failed to persist module '{$moduleName}' metadata: ".$e->getMessage());
        }
    }

    /**
     * Return the first existing class from the candidate list, or null.
     *
     * @param  list<string>  $candidates
     * @return class-string|null
     */
    protected function resolveModuleClass(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Check if module dependencies are met.
     */
    protected function checkDependencies(ModuleInterface $module): bool
    {
        foreach ($module->getDependencies() as $dependency) {
            $dependencyModule = $this->get($dependency);
            if (! $dependencyModule || ! $dependencyModule->isEnabled()) {
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
        return $this->enabled()->contains(function (ModuleInterface $module) use ($moduleName) {
            return in_array($moduleName, $module->getDependencies(), true);
        });
    }

    /**
     * Get module information for display.
     *
     * @return array<string, mixed>
     */
    public function getModuleInfo(string $name): array
    {
        $module = $this->get($name);

        if (! $module) {
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
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllModulesInfo(): array
    {
        /** @var array<int, array<string, mixed>> $info */
        $info = $this->modules->map(function (ModuleInterface $module) {
            return $this->getModuleInfo($module->getName());
        })->values()->toArray();

        return $info;
    }

    /**
     * Clear the module cache.
     */
    public function clearCache(): void
    {
        $cacheKey = config('modules.cache_key', 'app.modules');
        Cache::forget(is_string($cacheKey) ? $cacheKey : 'app.modules');
    }

    /**
     * Check module health status.
     *
     * @return array<string, mixed>
     */
    public function checkHealth(string $name): array
    {
        $module = $this->get($name);

        if (! $module) {
            return [
                'healthy' => false,
                'errors' => ['Module not found'],
            ];
        }

        $errors = [];
        $warnings = [];

        // Check if module class exists
        $moduleClass = get_class($module);
        if (! class_exists($moduleClass)) {
            $errors[] = "Module class {$moduleClass} not found";
        }

        // Check dependencies
        foreach ($module->getDependencies() as $dependency) {
            $dependencyModule = $this->get($dependency);
            if (! $dependencyModule) {
                $errors[] = "Dependency {$dependency} not found";
            } elseif (! $dependencyModule->isEnabled()) {
                $warnings[] = "Dependency {$dependency} is disabled";
            }
        }

        // Check if module is enabled but has unmet dependencies
        if ($module->isEnabled() && ! $this->checkDependencies($module)) {
            $errors[] = 'Module is enabled but has unmet dependencies';
        }

        return [
            'healthy' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
