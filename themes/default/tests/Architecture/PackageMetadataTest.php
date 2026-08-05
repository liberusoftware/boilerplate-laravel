<?php

use Illuminate\Support\ServiceProvider;

it('exposes complete and internally consistent theme metadata', function () {
    $theme = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($theme.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(file_get_contents($theme.'/theme.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toHaveKeys([
        'name', 'display_name', 'version', 'provider', 'type', 'parent',
        'optimized_for', 'tested_with', 'required_capabilities',
        'optional_capabilities', 'supports', 'assets',
    ])->and($composer['type'])->toBe('liberu-theme')
        ->and($composer['version'])->toBe($manifest['version'])
        ->and($composer['extra']['liberu']['name'])->toBe($manifest['name'])
        ->and(class_exists($manifest['provider']))->toBeTrue()
        ->and(is_subclass_of($manifest['provider'], ServiceProvider::class))->toBeTrue();

    foreach (['css', 'js'] as $kind) {
        foreach ($manifest['assets'][$kind] ?? [] as $asset) {
            expect($theme.'/'.$asset)->toBeFile();
        }
    }
});
