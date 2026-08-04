<?php

declare(strict_types=1);

use Liberu\PackageTestbench\BoundaryAssertions;
use Liberu\PackageTestbench\PackageRoot;

/**
 * PROTOTYPE - throwaway.
 * OPTION A: the package opts in explicitly. Two lines, but visible in the repo.
 */
it('honours the Liberu package boundary', function () {
    $root = PackageRoot::locate(__DIR__);
    BoundaryAssertions::metadataIsConsistent($root);
    BoundaryAssertions::declaredProviderExists($root);
});
