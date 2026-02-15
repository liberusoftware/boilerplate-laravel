<?php

namespace App\Modules;

use ReflectionClass;
use Artisan;
use App\Modules\Contracts\ModuleInterface;
use App\Modules\Traits\HasModuleHooks;
use App\Modules\Traits\Configurable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

abstract class BaseModule implements ModuleInterface
{
    use HasModuleHooks, Configurable;

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
        try {
            $record = \App\Models\Module::findByName($this->getName());
            if ($record !== null) {
                return (bool) $record->enabled;
            }
        } catch (\Throwable $e) {
            \Log::debug("Could not read module state from DB for {$this->getName()}: " . $e->getMessage());
        }

        // Fallback to property if present
        return $this->config['enabled'] ?? false;
    }

    /**
     * Enable the module.
     */
    public function enable(): void
    {
        if ($this->isEnabled()) {
            \Log::info("Module {$this->getName()} is already enabled, skipping enable operation.");
            return;
        }

        \Log::info("Enabling module: {$this->getName()}");

        // Execute before_enable hook
        $this->executeHook('before_enable', $this);

        // Run onEnable hook
        if (method_exists($this, 'onEnable')) {
            try {
                $this->onEnable();
                \Log::info("Module {$this->getName()} onEnable hook executed successfully.");
            } catch (\Throwable $e) {
                \Log::warning("onEnable failed for {$this->getName()}: " . $e->getMessage());
                throw $e; // Re-throw to allow caller to handle
            }
        }

        // Dispatch event
        try {
            event(new \App\Modules\Events\ModuleEnabled($this->getName()));
            \Log::info("ModuleEnabled event dispatched for {$this->getName()}");
        } catch (\Throwable $e) {
            \Log::debug("Failed to dispatch ModuleEnabled event for {$this->getName()}: " . $e->getMessage());
        }

        // Execute after_enable hook
        $this->executeHook('after_enable', $this);
    }

    /**
     * Disable the module.
     */
    public function disable(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Execute before_disable hook
        $this->executeHook('before_disable', $this);

        if (method_exists($this, 'onDisable')) {
            try {
                $this->onDisable();
            } catch (\Throwable $e) {
                \Log::warning("onDisable failed for {$this->getName()}: " . $e->getMessage());
            }
        }

        try {
            event(new \App\Modules\Events\ModuleDisabled($this->getName()));
        } catch (\Throwable $e) {
            \Log::debug("Failed to dispatch ModuleDisabled event for {$this->getName()}: " . $e->getMessage());
        }

        // Execute after_disable hook
        $this->executeHook('after_disable', $this);
    }

    /**
     * Install the module.
     */
    public function install(): void
    {
        \Log::info("Installing module: {$this->getName()}");

        // Execute before_install hook
        $this->executeHook('before_install', $this);

        try {
            $this->runMigrations();
            \Log::info("Migrations completed for module: {$this->getName()}");
        } catch (\Throwable $e) {
            \Log::error("Migration failed for module {$this->getName()}: " . $e->getMessage());
            throw $e;
        }

        try {
            $this->publishAssets();
            \Log::info("Assets published for module: {$this->getName()}");
        } catch (\Throwable $e) {
            \Log::warning("Asset publishing failed for module {$this->getName()}: " . $e->getMessage());
            // Continue anyway - assets are optional
        }

        $this->onInstall();
        $this->enable();

        // Execute after_install hook
        $this->executeHook('after_install', $this);
        
        \Log::info("Module {$this->getName()} installed successfully");
    }

    /**
     * Uninstall the module.
     */
    public function uninstall(): void
    {
        // Execute before_uninstall hook
        $this->executeHook('before_uninstall', $this);

        $this->disable();
        $this->rollbackMigrations();
        $this->removeAssets();
        $this->onUninstall();

        // Execute after_uninstall hook
        $this->executeHook('after_uninstall', $this);
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
        $reflection = new ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * Run module migrations.
     */
    protected function runMigrations(): void
    {
        $migrationsPath = $this->getModulePath() . '/database/migrations';
        
        if (File::exists($migrationsPath)) {
            Artisan::call('migrate', [
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
        $migrationsPath = $this->getModulePath() . '/database/migrations';
        
        if (!File::exists($migrationsPath)) {
            return;
        }

        try {
            Artisan::call('migrate:rollback', [
                '--path' => 'app/Modules/' . $this->name . '/database/migrations',
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            \Log::warning("Failed to rollback migrations for {$this->getName()}: " . $e->getMessage());
        }
    }

    /**
     * Publish module assets.
     */
    protected function publishAssets(): void
    {
        Artisan::call('vendor:publish', [
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