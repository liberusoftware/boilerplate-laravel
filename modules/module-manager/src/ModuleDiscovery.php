<?php

namespace Liberu\Foundation\ModuleManager;

use Composer\InstalledVersions;
use Liberu\Foundation\ModuleManager\Exceptions\InvalidManifest;

final class ModuleDiscovery
{
    /** @param list<string> $paths */
    public function discover(array $paths): ModuleRegistry
    {
        $modules = [];
        $capabilities = [];
        $packageNames = [];

        if (class_exists(InstalledVersions::class)) {
            foreach (InstalledVersions::getInstalledPackagesByType('liberu-module') as $package) {
                $installPath = InstalledVersions::getInstallPath($package);
                if (is_string($installPath)) {
                    $paths[] = dirname($installPath);
                }
            }
        }

        $paths = array_values(array_unique(array_filter(array_map(
            static fn (string $path): string|false => realpath($path),
            $paths,
        ))));

        foreach ($paths as $root) {
            foreach (glob(rtrim($root, '/').'/*/module.json') ?: [] as $path) {
                $manifest = Manifest::fromFile($path);
                if (! is_file($manifest->path.'/composer.json')) {
                    throw new InvalidManifest("Module [{$manifest->name()}] has no composer.json.");
                }
                $composer = json_decode((string) file_get_contents($manifest->path.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
                $package = $composer['name'] ?? null;
                if (! is_string($package)) {
                    throw new InvalidManifest("Module [{$manifest->name()}] has a missing or duplicate Composer package name.");
                }
                if (isset($modules[$manifest->name()])) {
                    if (($packageNames[$package] ?? null) === $manifest->name()
                        && $modules[$manifest->name()]->version() === $manifest->version()) {
                        continue;
                    }
                    throw new InvalidManifest("Duplicate module [{$manifest->name()}].");
                }
                if (isset($packageNames[$package])) {
                    throw new InvalidManifest("Module [{$manifest->name()}] has a missing or duplicate Composer package name.");
                }
                $packageNames[$package] = $manifest->name();
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
