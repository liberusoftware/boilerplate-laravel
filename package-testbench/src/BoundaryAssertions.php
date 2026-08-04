<?php

declare(strict_types=1);

namespace Liberu\PackageTestbench;

use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Assert;

/**
 * PROTOTYPE - throwaway.
 *
 * The reusable boundary assertions. Today these are the two checks every
 * package duplicates; they live here once instead of 44 times.
 */
final class BoundaryAssertions
{
    public const VENDOR = 'liberu/';

    public static function metadataIsConsistent(string $root): void
    {
        $composer = PackageRoot::composer($root);
        $manifest = PackageRoot::manifest($root);

        $declared = array_filter(
            $composer['require'] ?? [],
            static fn (string $c, string $p): bool => str_starts_with($p, self::VENDOR),
            ARRAY_FILTER_USE_BOTH,
        );

        Assert::assertContains($composer['type'], ['liberu-module', 'liberu-theme']);
        Assert::assertSame($manifest['version'], $composer['version']);
        Assert::assertSame($manifest['name'], $composer['extra']['liberu']['name']);
        Assert::assertSame($declared, $manifest['requires']['packages'] ?? []);
    }

    public static function declaredProviderExists(string $root): void
    {
        $provider = PackageRoot::manifest($root)['provider'] ?? null;

        if ($provider === null) {
            return; // contract-only packages expose no runtime provider
        }

        Assert::assertTrue(class_exists($provider), "Provider {$provider} does not exist");
        Assert::assertTrue(is_subclass_of($provider, ServiceProvider::class));
    }
}
