<?php

namespace Liberu\Foundation\Theme\Discovery;

use Illuminate\Support\Facades\File;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\Theme\Manifests\ThemeManifest;

final class ThemeDiscovery
{
    public function discover(string $path): array
    {
        if (! File::isDirectory($path)) {
            throw new InvalidTheme('The tracked themes directory is missing.');
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
