<?php

namespace Liberu\Foundation\ModuleManager;

use Composer\InstalledVersions;
use Composer\Semver\Semver;
use Illuminate\Support\ServiceProvider;

final class ModuleValidator
{
    /** @return list<string> */
    public function validate(ModuleRegistry $registry, string $laravelVersion): array
    {
        $errors = [];

        foreach ($registry->all() as $manifest) {
            $composerPath = $manifest->path.'/composer.json';
            $composer = json_decode((string) file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);

            if (($composer['extra']['liberu']['name'] ?? null) !== $manifest->name()) {
                $errors[] = "{$manifest->name()}: Composer extra.liberu.name does not match the manifest.";
            }

            if (($composer['type'] ?? null) !== 'liberu-module') {
                $errors[] = "{$manifest->name()}: Composer type must be liberu-module.";
            }

            $packageName = $composer['name'] ?? null;
            if (! is_string($packageName) || ! InstalledVersions::isInstalled($packageName)) {
                $errors[] = "{$manifest->name()}: Composer package is not installed.";
            } elseif (InstalledVersions::getPrettyVersion($packageName) !== $manifest->version()) {
                $errors[] = "{$manifest->name()}: installed Composer version does not match the manifest.";
            }

            $composerPackages = array_filter(
                $composer['require'] ?? [],
                static fn (string $package): bool => str_starts_with($package, 'liberusoftware/'),
                ARRAY_FILTER_USE_KEY,
            );
            if ($composerPackages !== $manifest->requiredPackages()) {
                $errors[] = "{$manifest->name()}: Composer and manifest Liberu dependencies differ.";
            }

            if (! class_exists($manifest->provider())) {
                $errors[] = "{$manifest->name()}: provider {$manifest->provider()} is not autoloadable.";
            } elseif (! is_subclass_of($manifest->provider(), ServiceProvider::class)) {
                $errors[] = "{$manifest->name()}: provider must extend Laravel's ServiceProvider.";
            }

            if ($manifest->phpConstraint() && ! Semver::satisfies(PHP_VERSION, $manifest->phpConstraint())) {
                $errors[] = "{$manifest->name()}: PHP ".PHP_VERSION." does not satisfy {$manifest->phpConstraint()}.";
            }

            if ($manifest->laravelConstraint() && ! Semver::satisfies($laravelVersion, $manifest->laravelConstraint())) {
                $errors[] = "{$manifest->name()}: Laravel {$laravelVersion} does not satisfy {$manifest->laravelConstraint()}.";
            }

            foreach (['admin', 'app'] as $panel) {
                foreach ($manifest->filamentPlugins($panel) as $plugin) {
                    if ($manifest->category() !== 'presentation') {
                        $errors[] = "{$manifest->name()}: only presentation modules may declare Filament plugins.";
                    }
                    if (! class_exists($plugin)) {
                        $errors[] = "{$manifest->name()}: presentation plugin {$plugin} is not autoloadable.";
                    }
                }
            }
        }

        try {
            $registry->resolve((array) config('modules.enabled', []), (array) config('modules.disabled', []));
        } catch (\Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        return $errors;
    }
}
