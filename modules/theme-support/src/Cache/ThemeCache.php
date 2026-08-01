<?php

namespace Liberu\Foundation\Theme\Cache;

use Liberu\Foundation\Theme\Exceptions\InvalidTheme;

final class ThemeCache
{
    public function load(string $path): array
    {
        $themes = unserialize((string) file_get_contents($path), ['allowed_classes' => true]);
        if (! is_array($themes)) {
            throw new InvalidTheme('Theme registry cache is invalid.');
        }

        return $themes;
    }

    public function write(array $themes, string $path): void
    {
        $tmp = $path.'.'.getmypid().'.tmp';
        if (file_put_contents($tmp, serialize($themes), LOCK_EX) === false || ! rename($tmp, $path)) {
            throw new InvalidTheme('Unable to write theme cache.');
        }
    }

    public function clear(string $path): void
    {
        if (is_file($path) && ! unlink($path)) {
            throw new InvalidTheme('Unable to clear theme cache.');
        }
    }
}
