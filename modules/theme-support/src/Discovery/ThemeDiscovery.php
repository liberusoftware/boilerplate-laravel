<?php

namespace Liberu\Foundation\Theme\Discovery;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\File;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\Theme\Manifests\ThemeManifest;

final class ThemeDiscovery
{
    /**
     * @param  list<string>|null  $installedPaths  where each `liberu-theme` Composer package
     *                                             is installed; read from Composer when null
     */
    public function __construct(private readonly ?array $installedPaths = null) {}

    /**
     * Every theme this application has, from the tracked tree and from Composer.
     *
     * Composer is what makes a theme reachable outside a composition: a package
     * that dev-requires a theme gets it under its own `vendor/`, where no tracked
     * tree exists at all. A composition installs its themes *into* the tracked
     * tree, so there the two sources name one directory — hence the dedupe on the
     * resolved real path, which runs before the name check so that two different
     * packages claiming one theme name still collide.
     *
     * @return array<string, ThemeManifest>
     */
    public function discover(string $path): array
    {
        $tracked = File::isDirectory($path) ? File::directories($path) : [];
        $trackedPaths = array_values(array_filter(array_map(realpath(...), $tracked)));

        $themes = [];
        $seen = [];
        foreach ([...$tracked, ...$this->installedPaths()] as $candidate) {
            $directory = realpath($candidate);
            if ($directory === false || isset($seen[$directory])) {
                continue;
            }
            $seen[$directory] = true;
            $manifestPath = $directory.'/theme.json';
            if (! File::isFile($manifestPath)) {
                continue;
            }
            $manifest = ThemeManifest::fromFile($manifestPath);
            // Only the tracked tree makes the directory name authoritative — the host
            // derives Vite inputs and public asset URLs from it. A Composer package
            // names itself in `extra.liberu.name` and lands wherever it is installed.
            if (in_array($directory, $trackedPaths, true) && $manifest->name() !== basename($directory)) {
                throw new InvalidTheme('Theme directory/name collision detected.');
            }
            if (isset($themes[$manifest->name()])) {
                throw new InvalidTheme('Theme directory/name collision detected.');
            }
            $composerPath = $directory.'/composer.json';
            if (! File::isFile($composerPath)) {
                throw new InvalidTheme("Theme [{$manifest->name()}] has no composer.json.");
            }
            $composer = json_decode(File::get($composerPath), true, flags: JSON_THROW_ON_ERROR);
            if (($composer['type'] ?? null) !== 'liberu-theme' || ($composer['extra']['liberu']['name'] ?? null) !== $manifest->name()) {
                throw new InvalidTheme("Theme [{$manifest->name()}] Composer metadata is inconsistent.");
            }
            if (! class_exists($manifest->provider())) {
                throw new InvalidTheme("Theme provider [{$manifest->provider()}] is not autoloadable.");
            }
            $themes[$manifest->name()] = $manifest;
        }

        if ($themes === []) {
            throw new InvalidTheme('No themes are installed.');
        }

        ksort($themes);

        return $themes;
    }

    /** @return list<string> */
    private function installedPaths(): array
    {
        if ($this->installedPaths !== null) {
            return $this->installedPaths;
        }
        if (! class_exists(InstalledVersions::class)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $package): ?string => InstalledVersions::getInstallPath($package),
            InstalledVersions::getInstalledPackagesByType('liberu-theme'),
        ), is_string(...)));
    }
}
