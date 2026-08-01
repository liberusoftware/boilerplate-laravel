<?php

namespace Liberu\Foundation\ModuleManager\Cache;

use Liberu\Foundation\ModuleManager\ModuleDiscovery;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use RuntimeException;

final class RegistryCache
{
    public function load(array $paths, bool $useCache, string $path): ModuleRegistry
    {
        if ($useCache && is_file($path)) {
            $registry = unserialize((string) file_get_contents($path), ['allowed_classes' => true]);
            if (! $registry instanceof ModuleRegistry) {
                throw new RuntimeException('The module registry cache is invalid; run module:clear.');
            }

            return $registry;
        }

        return (new ModuleDiscovery())->discover($paths);
    }

    public function write(ModuleRegistry $registry, string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $temporary = $path.'.'.getmypid().'.tmp';
        if (file_put_contents($temporary, serialize($registry), LOCK_EX) === false || ! rename($temporary, $path)) {
            throw new RuntimeException('Unable to write the module registry cache.');
        }
    }

    public function clear(string $path): void
    {
        if (is_file($path) && ! unlink($path)) {
            throw new RuntimeException('Unable to clear the module registry cache.');
        }
    }
}
