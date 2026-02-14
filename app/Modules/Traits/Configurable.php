<?php

namespace App\Modules\Traits;

use Illuminate\Support\Facades\Config;

/**
 * Trait Configurable
 * 
 * Provides configuration management for modules.
 */
trait Configurable
{
    /**
     * Get a configuration value for the module.
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function config(string $key, mixed $default = null): mixed
    {
        $moduleName = strtolower($this->getName());
        return Config::get("{$moduleName}.{$key}", $default);
    }

    /**
     * Set a runtime configuration value for the module.
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     */
    public function setConfig(string $key, mixed $value): void
    {
        $moduleName = strtolower($this->getName());
        Config::set("{$moduleName}.{$key}", $value);
    }

    /**
     * Check if a configuration key exists.
     */
    public function hasConfig(string $key): bool
    {
        $moduleName = strtolower($this->getName());
        return Config::has("{$moduleName}.{$key}");
    }

    /**
     * Get all configuration for the module.
     */
    public function getAllConfig(): array
    {
        $moduleName = strtolower($this->getName());
        return Config::get($moduleName, []);
    }

    /**
     * Merge configuration array into module config.
     */
    public function mergeConfig(array $config): void
    {
        $moduleName = strtolower($this->getName());
        $existing = Config::get($moduleName, []);
        Config::set($moduleName, array_merge($existing, $config));
    }
}
