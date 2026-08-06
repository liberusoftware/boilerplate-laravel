<?php

use Liberu\Foundation\ModuleManager\Manifest;

// Reads this package's own manifest rather than globbing the consuming application's
// modules/ directory, so the suite passes wherever the package is installed. The
// whole-fleet check is a composition concern and lives in the host's architecture rules.
it('parses this package manifest through the canonical value object', function () {
    $manifest = Manifest::fromFile(dirname(__DIR__, 2).'/module.json');

    expect($manifest->name())->toBe('module-manager')
        ->and($manifest->version())->toMatch('/^\d+\.\d+\.\d+$/')
        ->and($manifest->capabilities())->toBeArray()->not->toBeEmpty();
});
