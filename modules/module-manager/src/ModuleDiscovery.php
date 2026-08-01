<?php

namespace Liberu\Foundation\ModuleManager;

use Liberu\Foundation\ModuleManager\Exceptions\InvalidManifest;

final class ModuleDiscovery
{
    /** @param list<string> $paths */
    public function discover(array $paths): ModuleRegistry
    {
        $modules = [];
        $capabilities = [];

        foreach ($paths as $root) {
            foreach (glob(rtrim($root, '/').'/*/module.json') ?: [] as $path) {
                $manifest = Manifest::fromFile($path);
                if (isset($modules[$manifest->name()])) {
                    throw new InvalidManifest("Duplicate module [{$manifest->name()}].");
                }
                if (! is_file($manifest->path.'/composer.json')) {
                    throw new InvalidManifest("Module [{$manifest->name()}] has no composer.json.");
                }
                foreach ($manifest->capabilities() as $capability) {
                    if (isset($capabilities[$capability])) {
                        throw new InvalidManifest("Duplicate capability [{$capability}].");
                    }
                    $capabilities[$capability] = $manifest->name();
                }
                $modules[$manifest->name()] = $manifest;
            }
        }

        ksort($modules);

        return new ModuleRegistry($modules);
    }
}
