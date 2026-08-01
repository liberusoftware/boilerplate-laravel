<?php

namespace Liberu\Foundation\ModuleManager;

use Liberu\Foundation\ModuleManager\Exceptions\DependencyResolutionFailed;

final class ModuleRegistry
{
    /** @param array<string, Manifest> $modules */
    public function __construct(private array $modules) {}

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    public function get(string $name): ?Manifest
    {
        return $this->modules[$name] ?? null;
    }

    /** @return array<string, Manifest> */
    public function all(): array
    {
        return $this->modules;
    }

    /** @return list<Manifest> */
    public function resolve(array $enabled, array $disabled = []): array
    {
        $selected = [];
        foreach ($this->modules as $name => $manifest) {
            if (! in_array($name, $disabled, true)
                && ($manifest->defaultEnabled() || in_array($name, $enabled, true))) {
                $selected[$name] = $manifest;
            }
        }

        $packageOwners = [];
        foreach ($this->modules as $name => $manifest) {
            $composerPath = $manifest->path.'/composer.json';
            if (! is_file($composerPath)) {
                continue;
            }
            $composer = json_decode((string) file_get_contents($composerPath), true);
            if (isset($composer['name'])) {
                $packageOwners[$composer['name']] = $name;
            }
        }

        foreach ($selected as $name => $manifest) {
            foreach ($manifest->requiredPackages() as $package => $constraint) {
                if (isset($packageOwners[$package]) && ! isset($selected[$packageOwners[$package]])) {
                    throw new DependencyResolutionFailed("Module [{$name}] requires enabled package [{$package} {$constraint}].");
                }
            }
        }

        $ordered = [];
        $visiting = [];
        $visit = function (string $name) use (&$visit, &$ordered, &$visiting, $selected, $packageOwners): void {
            if (isset($ordered[$name])) {
                return;
            }
            if (isset($visiting[$name])) {
                throw new DependencyResolutionFailed("Circular module dependency involving [{$name}].");
            }
            $visiting[$name] = true;
            foreach ($selected[$name]->requiredPackages() as $package => $_) {
                if (isset($packageOwners[$package], $selected[$packageOwners[$package]])) {
                    $visit($packageOwners[$package]);
                }
            }
            unset($visiting[$name]);
            $ordered[$name] = $selected[$name];
        };

        $names = array_keys($selected);
        sort($names);
        foreach ($names as $name) {
            $visit($name);
        }

        return array_values($ordered);
    }
}
