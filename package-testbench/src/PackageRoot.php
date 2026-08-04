<?php

declare(strict_types=1);

namespace Liberu\PackageTestbench;

/**
 * PROTOTYPE - throwaway.
 *
 * Locates the package under test and reads its manifest. This is the whole
 * "package-loading declaration": the package declares nothing extra, because
 * module.json already declares its provider and its Liberu dependencies.
 */
final class PackageRoot
{
    public static function locate(string $from): string
    {
        $dir = is_dir($from) ? $from : dirname($from);

        while ($dir !== dirname($dir)) {
            if (is_file($dir.'/module.json') || is_file($dir.'/theme.json')) {
                return $dir;
            }
            $dir = dirname($dir);
        }

        throw new \RuntimeException("No module.json or theme.json found above {$from}");
    }

    /** @return array<string, mixed> */
    public static function manifest(string $root): array
    {
        $file = is_file($root.'/module.json') ? $root.'/module.json' : $root.'/theme.json';

        return json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    public static function composer(string $root): array
    {
        return json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
