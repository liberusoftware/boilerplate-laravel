<?php

namespace Liberu\Foundation\Theme\Tests\Security;

use Liberu\Foundation\Theme\Cache\ThemeCache;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use PHPUnit\Framework\TestCase;

final class ThemeCacheSecurityTest extends TestCase
{
    public function test_cache_round_trip(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'theme-security-');
        try {
            $cache = new ThemeCache();
            $cache->write([], $path);
            self::assertSame([], $cache->load($path));
        } finally {
            unlink($path);
        }
    }

    public function test_cache_does_not_instantiate_unexpected_classes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'theme-security-');
        CachePayload::$awakened = false;
        file_put_contents($path, serialize(['payload' => new CachePayload()]));
        try {
            (new ThemeCache())->load($path);
            self::fail('An unexpected cached object was accepted.');
        } catch (InvalidTheme) {
            self::assertFalse(CachePayload::$awakened);
        } finally {
            unlink($path);
        }
    }
}

final class CachePayload
{
    public static bool $awakened = false;

    public function __wakeup(): void
    {
        self::$awakened = true;
    }
}
