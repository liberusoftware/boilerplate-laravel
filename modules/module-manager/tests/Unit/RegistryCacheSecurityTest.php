<?php

namespace Liberu\Foundation\ModuleManager\Tests\Security;

use Liberu\Foundation\ModuleManager\Cache\RegistryCache;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RegistryCacheSecurityTest extends TestCase
{
    public function test_cache_round_trip(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'registry-security-');
        try {
            $cache = new RegistryCache();
            $cache->write(new ModuleRegistry([]), $path);
            self::assertSame([], $cache->load([], true, $path)->all());
        } finally {
            unlink($path);
        }
    }

    public function test_legacy_private_property_caches_remain_readable(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'registry-security-');
        $class = ModuleRegistry::class;
        $property = "\0".$class."\0modules";
        file_put_contents($path, sprintf('O:%d:"%s":1:{s:%d:"%s";a:0:{}}', strlen($class), $class, strlen($property), $property));
        try {
            self::assertSame([], (new RegistryCache())->load([], true, $path)->all());
        } finally {
            unlink($path);
        }
    }

    public function test_cache_does_not_instantiate_unexpected_classes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'registry-security-');
        CachePayload::$awakened = false;
        file_put_contents($path, serialize(new ModuleRegistry(['payload' => new CachePayload()])));
        try {
            (new RegistryCache())->load([], true, $path);
            self::fail('An unexpected cached object was accepted.');
        } catch (RuntimeException) {
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
