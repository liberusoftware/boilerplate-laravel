<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

abstract class BaseModule implements ModuleInterface
{
    protected string $name;
    protected string $version;
    protected string $description;
    protected array $dependencies = [];
    protected array $config = [];

    public function __construct()
    {
        $this->loadModuleInfo();
    }

    /**
     * Get the module name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the module version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get the module description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the module dependencies.
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Check if the module is enabled.
     */
    public function isEnabled(): bool
    {
        return Cache::get("module.{$this->name}.enabled", false);
    }

    /**
     * Enable the module.
     */
    public function enable(): void
    {
        Cache::put("module.{$this->name}.enabled", true);
        $this->onEnable();
    }

    /**
     * Disable the module.
     */
    public function disable(): void
    {
        Cache::put("module.{$this->name}.enabled", false);
        $this->onDisable();
    }

    /**
     * Install the module.
     */
    public function install(): void
    {
        $this->runMigrations();
        $this->publishAssets();
        $this->onInstall();
        $this->enable();
    }

    /**
     * Uninstall the module.
     */
    public function uninstall(): void
    {
        $this->disable();
        $this->rollbackMigrations();
        $this->removeAssets();
        $this->onUninstall();
    }

    /**
     * Get module configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Load module information from module.json file.
     */
    protected function loadModuleInfo(): void
    {
        $modulePath = $this->getModulePath();
        $moduleInfoPath = $modulePath . '/module.json';

        if (File::exists($moduleInfoPath)) {
            $moduleInfo = json_decode(File::get($moduleInfoPath), true);
            
            $this->name = $moduleInfo['name'] ?? class_basename($this);
            $this->version = $moduleInfo['version'] ?? '1.0.0';
            $this->description = $moduleInfo['description'] ?? '';
            $this->dependencies = $moduleInfo['dependencies'] ?? [];
            $this->config = $moduleInfo['config'] ?? [];
        }
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * Run module migrations.
     */
    protected function runMigrations(): void
    {
        $migrationsPath = $this->getModulePath() . '/database/migrations';
        
        if (File::exists($migrationsPath)) {
            \Artisan::call('migrate', [
                '--path' => 'app/Modules/' . $this->name . '/database/migrations',
                '--force' => true,
            ]);
        }
    }

    /**
     * Rollback module migrations.
     */
    protected function rollbackMigrations(): void
    {
        // Implementation depends on specific requirements
        // Could use migration tags or custom rollback logic
    }

    /**
     * Publish module assets.
     */
    protected function publishAssets(): void
    {
        \Artisan::call('vendor:publish', [
            '--tag' => strtolower($this->name) . '-assets',
            '--force' => true,
        ]);
    }

    /**
     * Remove module assets.
     */
    protected function removeAssets(): void
    {
        $assetsPath = public_path("modules/{$this->name}");
        if (File::exists($assetsPath)) {
            File::deleteDirectory($assetsPath);
        }
    }

    /**
     * Hook called when module is enabled.
     */
    protected function onEnable(): void
    {
        // Override in child classes
    }

    /**
     * Hook called when module is disabled.
     */
    protected function onDisable(): void
    {
        // Override in child classes
    }

    /**
     * Hook called when module is installed.
     */
    protected function onInstall(): void
    {
        // Override in child classes
    }

    /**
     * Hook called when module is uninstalled.
     */
    protected function onUninstall(): void
    {
        // Override in child classes
    }
}