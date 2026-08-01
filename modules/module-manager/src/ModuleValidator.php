<?php

namespace Liberu\Foundation\ModuleManager;

use Composer\Semver\Semver;

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

            if (! class_exists($manifest->provider())) {
                $errors[] = "{$manifest->name()}: provider {$manifest->provider()} is not autoloadable.";
            }

            if ($manifest->phpConstraint() && ! Semver::satisfies(PHP_VERSION, $manifest->phpConstraint())) {
                $errors[] = "{$manifest->name()}: PHP ".PHP_VERSION." does not satisfy {$manifest->phpConstraint()}.";
            }

            if ($manifest->laravelConstraint() && ! Semver::satisfies($laravelVersion, $manifest->laravelConstraint())) {
                $errors[] = "{$manifest->name()}: Laravel {$laravelVersion} does not satisfy {$manifest->laravelConstraint()}.";
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
