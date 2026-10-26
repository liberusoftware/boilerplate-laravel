<?php

namespace Liberu\Foundation\Theme\Cache;

use Illuminate\Filesystem\Filesystem;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\Theme\Manifests\ThemeManifest;

final class ThemeCache
{
    private readonly Filesystem $files;

    public function __construct(?Filesystem $files = null)
    {
        $this->files = $files ?? new Filesystem();
    }

    public function load(string $path): array
    {
        $themes = unserialize($this->files->get($path), ['allowed_classes' => [ThemeManifest::class]]);
        if (! is_array($themes) || $this->containsUnexpectedObject($themes)) {
            throw new InvalidTheme('Theme registry cache is invalid.');
        }

        return $themes;
    }

    private function containsUnexpectedObject(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value) && $this->containsUnexpectedObject($value)) {
                return true;
            }
            if (is_object($value) && ! $value instanceof ThemeManifest) {
                return true;
            }
        }

        return false;
    }

    public function write(array $themes, string $path): void
    {
        $tmp = $path.'.'.getmypid().'.tmp';
        if ($this->files->put($tmp, serialize($themes), true) === false || ! $this->files->move($tmp, $path)) {
            throw new InvalidTheme('Unable to write theme cache.');
        }
    }

    public function clear(string $path): void
    {
        if ($this->files->isFile($path) && ! $this->files->delete($path)) {
            throw new InvalidTheme('Unable to clear theme cache.');
        }
    }
}
