<?php

namespace Liberu\Foundation\Theme\Discovery;

use Illuminate\Support\Facades\File;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\Theme\Manifests\ThemeManifest;

final class ThemeDiscovery
{
    /**
     * Every theme installed under the given path.
     *
     * A path that is not a directory yields no themes rather than an error. The
     * provider discovers during `register()`, so throwing here made this package
     * impossible to boot in any application without a `themes/` tree — including
     * its own test application, which is why its standalone suite could not run.
     * A composition that has lost its themes is caught where that is knowable:
     * the host asserts the directory is populated, and `theme:validate` reports
     * on it.
     */
    public function discover(string $path): array
    {
        if (! File::isDirectory($path)) {
            return [];
        }$themes = [];
        foreach (File::directories($path) as $directory) {
            $manifestPath = $directory.'/theme.json';
            if (! File::isFile($manifestPath)) {
                continue;
            }$manifest = ThemeManifest::fromFile($manifestPath);
            if ($manifest->name() !== basename($directory) || isset($themes[$manifest->name()])) {
                throw new InvalidTheme('Theme directory/name collision detected.');
            }$composerPath = $directory.'/composer.json';
            if (! File::isFile($composerPath)) {
                throw new InvalidTheme("Theme [{$manifest->name()}] has no composer.json.");
            }$composer = json_decode(File::get($composerPath), true, flags: JSON_THROW_ON_ERROR);
            if (($composer['type'] ?? null) !== 'liberu-theme' || ($composer['extra']['liberu']['name'] ?? null) !== $manifest->name()) {
                throw new InvalidTheme("Theme [{$manifest->name()}] Composer metadata is inconsistent.");
            }if (! class_exists($manifest->provider())) {
                throw new InvalidTheme("Theme provider [{$manifest->provider()}] is not autoloadable.");
            }$themes[$manifest->name()] = $manifest;
        }ksort($themes);

        return $themes;
    }
}
