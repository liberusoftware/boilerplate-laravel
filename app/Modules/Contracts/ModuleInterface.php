<?php

namespace App\Modules\Contracts;

interface ModuleInterface
{
    /**
     * Get the module name.
     */
    public function getName(): string;

    /**
     * Get the module version.
     */
    public function getVersion(): string;

    /**
     * Get the module description.
     */
    public function getDescription(): string;

    /**
     * Get the module dependencies.
     */
    public function getDependencies(): array;

    /**
     * Check if the module is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Enable the module.
     */
    public function enable(): void;

    /**
     * Disable the module.
     */
    public function disable(): void;

    /**
     * Install the module.
     */
    public function install(): void;

    /**
     * Uninstall the module.
     */
    public function uninstall(): void;

    /**
     * Get module configuration.
     */
    public function getConfig(): array;
}