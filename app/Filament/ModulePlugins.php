<?php

namespace App\Filament;

use Filament\Contracts\Plugin;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use RuntimeException;

final readonly class ModulePlugins
{
    public function __construct(private ModuleRegistry $modules) {}

    /** @return list<Plugin> */
    public function forPanel(string $panel): array
    {
        $plugins = [];
        $resolved = $this->modules->resolve((array) config('modules.enabled', []), (array) config('modules.disabled', []));

        foreach ($resolved as $module) {
            foreach ($module->filamentPlugins($panel) as $pluginClass) {
                $plugin = method_exists($pluginClass, 'make') ? $pluginClass::make() : app($pluginClass);
                if (! $plugin instanceof Plugin) {
                    throw new RuntimeException("Module [{$module->name()}] returned an invalid Filament plugin.");
                }
                if (isset($plugins[$plugin->getId()])) {
                    throw new RuntimeException("Duplicate Filament plugin id [{$plugin->getId()}].");
                }
                $plugins[$plugin->getId()] = $plugin;
            }
        }

        return array_values($plugins);
    }
}
