<?php

declare(strict_types=1);

use Liberu\PackageTestbench\BoundaryAssertions;
use Liberu\PackageTestbench\PackageRoot;
use Liberu\PackageTestbench\PackageTestCase;

/**
 * PROTOTYPE - throwaway.
 *
 * OPTION B: the testbench ships the boundary tests themselves. A consuming
 * package adds ZERO test files - its phpunit.xml simply points a testsuite at
 * this directory inside vendor/. The package under test is discovered from cwd.
 */
uses(PackageTestCase::class);

it('exposes internally consistent package metadata', function () {
    BoundaryAssertions::metadataIsConsistent(PackageRoot::locate(getcwd()));
});

it('registers the service provider its manifest declares', function () {
    $root = PackageRoot::locate(getcwd());
    BoundaryAssertions::declaredProviderExists($root);

    $provider = PackageRoot::manifest($root)['provider'] ?? null;

    if ($provider !== null) {
        expect($this->app->getProvider($provider))->not->toBeNull();
    }
});
