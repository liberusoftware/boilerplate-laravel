<?php

namespace Liberu\Foundation\ModuleManager\Cache;

use Illuminate\Filesystem\Filesystem;
use Liberu\Foundation\ModuleManager\ModuleDiscovery;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use RuntimeException;

final class RegistryCache
{
    private readonly Filesystem $files;

    public function __construct(?Filesystem $files = null)
    {
        $this->files = $files ?? new Filesystem();
    }

    public function load(array $paths, bool $useCache, string $path): ModuleRegistry
    {
        if ($useCache && $this->files->isFile($path)) {
            $registry = unserialize($this->files->get($path), ['allowed_classes' => true]);
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
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
        $temporary = $path.'.'.getmypid().'.tmp';
        if ($this->files->put($temporary, serialize($registry), true) === false || ! $this->files->move($temporary, $path)) {
            throw new RuntimeException('Unable to write the module registry cache.');
        }
    }

    public function clear(string $path): void
    {
        if ($this->files->isFile($path) && ! $this->files->delete($path)) {
            throw new RuntimeException('Unable to clear the module registry cache.');
        }
    }
}
