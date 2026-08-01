<?php

namespace Liberu\Foundation\ModuleManager;

use Composer\InstalledVersions;
use Composer\Semver\Semver;
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

    public function enabled(string $name, array $enabled = [], array $disabled = []): bool
    {
        foreach ($this->resolve($enabled, $disabled) as $manifest) {
            if ($manifest->name() === $name) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, Manifest> */
    public function all(): array
    {
        return $this->modules;
    }

    /** @return array<string, list<string>> */
    public function searchFeatures(string $query = ''): array
    {
        $query = mb_strtolower(trim($query));
        $matches = [];

        foreach ($this->modules as $name => $manifest) {
            $features = array_values(array_filter(
                $manifest->features(),
                fn (string $feature): bool => $query === '' || str_contains(mb_strtolower($feature), $query),
            ));
            if ($features !== []) {
                $matches[$name] = $features;
            }
        }

        ksort($matches);

        return $matches;
    }

    /** @return list<Manifest> */
    public function providingFeature(string $feature): array
    {
        $feature = mb_strtolower(trim($feature));

        return array_values(array_filter(
            $this->modules,
            fn (Manifest $manifest): bool => in_array($feature, array_map('mb_strtolower', $manifest->features()), true),
        ));
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
        $capabilityOwners = [];
        foreach ($this->modules as $name => $manifest) {
            $composerPath = $manifest->path.'/composer.json';
            if (! is_file($composerPath)) {
                continue;
            }
            $composer = json_decode((string) file_get_contents($composerPath), true);
            if (isset($composer['name'])) {
                $packageOwners[$composer['name']] = $name;
            }
            foreach ($manifest->capabilities() as $capability) {
                $capabilityOwners[$capability] = $name;
            }
        }

        foreach ($selected as $name => $manifest) {
            foreach ($manifest->requiredPackages() as $package => $constraint) {
                if (! isset($packageOwners[$package])) {
                    if (! InstalledVersions::isInstalled($package)
                        || ! Semver::satisfies((string) InstalledVersions::getPrettyVersion($package), $constraint)) {
                        throw new DependencyResolutionFailed("Module [{$name}] requires missing or incompatible library [{$package} {$constraint}].");
                    }

                    continue;
                }
                $owner = $packageOwners[$package];
                if (! Semver::satisfies($this->modules[$owner]->version(), $constraint)) {
                    throw new DependencyResolutionFailed("Module [{$name}] requires [{$package} {$constraint}], installed manifest is {$this->modules[$owner]->version()}.");
                }
                if (! isset($selected[$owner])) {
                    throw new DependencyResolutionFailed("Module [{$name}] requires enabled package [{$package} {$constraint}].");
                }
            }
            foreach ($manifest->requiredCapabilities() as $capability => $constraint) {
                if (! isset($capabilityOwners[$capability])) {
                    throw new DependencyResolutionFailed("Module [{$name}] requires missing capability [{$capability}].");
                }
                $owner = $capabilityOwners[$capability];
                if (! isset($selected[$owner]) || ! Semver::satisfies($selected[$owner]->version(), $constraint)) {
                    throw new DependencyResolutionFailed("Module [{$name}] requires enabled capability [{$capability} {$constraint}].");
                }
            }
        }

        $ordered = [];
        $visiting = [];
        $visit = function (string $name) use (&$visit, &$ordered, &$visiting, $selected, $packageOwners, $capabilityOwners): void {
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
            foreach ($selected[$name]->requiredCapabilities() as $capability => $_) {
                if (isset($capabilityOwners[$capability], $selected[$capabilityOwners[$capability]])) {
                    $visit($capabilityOwners[$capability]);
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
