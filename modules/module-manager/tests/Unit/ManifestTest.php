<?php

use Liberu\Foundation\ModuleManager\Manifest;

it('parses every repository module manifest through the canonical value object', function () {
    $root = dirname(__DIR__, 4);
    foreach (glob($root.'/modules/*/module.json') ?: [] as $path) {
        $manifest = Manifest::fromFile($path);
        expect($manifest->name())->not->toBeEmpty()
            ->and($manifest->version())->toMatch('/^\d+\.\d+\.\d+$/')
            ->and($manifest->capabilities())->toBeArray();
    }
});
