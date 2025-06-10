<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Collection;
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
            throw new \Exception("Module {$name} has unmet dependencies.");
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
            throw new \Exception("Cannot uninstall module {$name} as other modules depend on it.");
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
        $modulesPath = app_path('Modules');
        
        if (!File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $this->loadModule($moduleName, $modulePath);
        }
    }

    /**
     * Load a specific module.
     */
    protected function loadModule(string $moduleName, string $modulePath): void
    {
        $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";
        
        if (class_exists($moduleClass)) {
            $module = new $moduleClass();
            if ($module instanceof ModuleInterface) {
                $this->register($module);
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
}