<?php

namespace App\Modules\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Modules\ModuleManager;
use App\Modules\Contracts\ModuleInterface;

/**
 * External Module Loader
 * 
 * Loads modules from external sources such as composer packages.
 */
class ExternalModuleLoader
{
    protected ModuleManager $moduleManager;
    protected array $loadedPaths = [];

    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Load modules from a specific path (e.g., vendor directory).
     *
     * @param string $path Path to scan for modules
     * @param string $namespace Base namespace for modules in this path
     */
    public function loadFromPath(string $path, string $namespace = 'Modules'): void
    {
        if (!File::exists($path) || !File::isDirectory($path)) {
            Log::debug("External module path does not exist: {$path}");
            return;
        }

        // Prevent loading same path multiple times
        if (in_array($path, $this->loadedPaths)) {
            return;
        }

        $this->loadedPaths[] = $path;

        $directories = File::directories($path);

        foreach ($directories as $directory) {
            $this->loadModuleFromDirectory($directory, $namespace);
        }
    }

    /**
     * Load a module from a specific directory.
     *
     * @param string $directory Directory containing the module
     * @param string $baseNamespace Base namespace
     */
    protected function loadModuleFromDirectory(string $directory, string $baseNamespace): void
    {
        $moduleName = basename($directory);
        
        // Look for module.json to identify this as a module
        $moduleJsonPath = $directory . '/module.json';
        if (!File::exists($moduleJsonPath)) {
            return;
        }

        // Try to find and load the module class
        $possibleClassNames = [
            "{$baseNamespace}\\{$moduleName}\\{$moduleName}Module",
            "{$baseNamespace}\\{$moduleName}\\Module",
            "{$baseNamespace}\\{$moduleName}\\{$moduleName}",
        ];

        foreach ($possibleClassNames as $className) {
            if (class_exists($className)) {
                try {
                    $module = new $className();
                    
                    if ($module instanceof ModuleInterface) {
                        $this->moduleManager->register($module);
                        Log::info("Loaded external module: {$moduleName} from {$directory}");
                        return;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Failed to instantiate module class {$className}: " . $e->getMessage());
                }
            }
        }

        Log::debug("No valid module class found in {$directory}");
    }

    /**
     * Load modules from composer packages.
     * 
     * Scans vendor directory for packages that have a modules/ subdirectory.
     */
    public function loadFromComposer(): void
    {
        $vendorPath = base_path('vendor');
        
        if (!File::exists($vendorPath)) {
            return;
        }

        // Get all vendor directories
        $vendors = File::directories($vendorPath);

        foreach ($vendors as $vendorDir) {
            $packages = File::directories($vendorDir);
            
            foreach ($packages as $packageDir) {
                // Check if package has a modules directory
                $modulesPath = $packageDir . '/modules';
                if (File::exists($modulesPath)) {
                    $packageName = basename(dirname($packageDir)) . '/' . basename($packageDir);
                    Log::info("Loading modules from composer package: {$packageName}");
                    $this->loadFromPath($modulesPath, $this->getNamespaceFromComposer($packageDir));
                }
            }
        }
    }

    /**
     * Get namespace from composer.json.
     *
     * @param string $packageDir Package directory path
     * @return string Base namespace
     */
    protected function getNamespaceFromComposer(string $packageDir): string
    {
        $composerJsonPath = $packageDir . '/composer.json';
        
        if (!File::exists($composerJsonPath)) {
            return 'Modules';
        }

        try {
            $composerData = json_decode(File::get($composerJsonPath), true);
            
            if (isset($composerData['autoload']['psr-4'])) {
                // Get first PSR-4 namespace
                $namespaces = array_keys($composerData['autoload']['psr-4']);
                if (!empty($namespaces)) {
                    return rtrim($namespaces[0], '\\');
                }
            }
        } catch (\Throwable $e) {
            Log::debug("Failed to parse composer.json in {$packageDir}: " . $e->getMessage());
        }

        return 'Modules';
    }

    /**
     * Register a module from a custom location.
     *
     * @param string $modulePath Full path to the module directory
     * @param string $moduleClass Full class name of the module
     */
    public function registerCustomModule(string $modulePath, string $moduleClass): bool
    {
        if (!File::exists($modulePath)) {
            Log::warning("Custom module path does not exist: {$modulePath}");
            return false;
        }

        if (!class_exists($moduleClass)) {
            Log::warning("Custom module class does not exist: {$moduleClass}");
            return false;
        }

        try {
            $module = new $moduleClass();
            
            if (!$module instanceof ModuleInterface) {
                Log::warning("Custom module class does not implement ModuleInterface: {$moduleClass}");
                return false;
            }

            $this->moduleManager->register($module);
            Log::info("Registered custom module: {$module->getName()} from {$modulePath}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to register custom module {$moduleClass}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all loaded external module paths.
     */
    public function getLoadedPaths(): array
    {
        return $this->loadedPaths;
    }
}
