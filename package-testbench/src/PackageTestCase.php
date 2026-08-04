<?php

declare(strict_types=1);

namespace Liberu\PackageTestbench;

use Orchestra\Testbench\TestCase;

/**
 * PROTOTYPE - throwaway.
 *
 * The one base case every Liberu package extends. It boots Testbench and
 * registers the provider the manifest declares - so a package needs no
 * getPackageProviders() override and no bootstrap of its own.
 */
abstract class PackageTestCase extends TestCase
{
    protected function packageRoot(): string
    {
        return PackageRoot::locate((new \ReflectionClass(static::class))->getFileName());
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        $provider = PackageRoot::manifest($this->packageRoot())['provider'] ?? null;

        return $provider === null ? [] : [$provider];
    }
}
