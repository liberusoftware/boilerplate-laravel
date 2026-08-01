<?php

use Illuminate\Support\ServiceProvider;

it('exposes internally consistent package metadata', function () {
    $module = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($module.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(file_get_contents($module.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
    $dependencies = array_filter($composer['require'] ?? [], fn ($constraint, $package) => str_starts_with($package, 'liberu/'), ARRAY_FILTER_USE_BOTH);

    expect($composer['type'])->toBe('liberu-module')
        ->and($composer['version'])->toBe($manifest['version'])
        ->and($composer['extra']['liberu']['name'])->toBe($manifest['name'])
        ->and($manifest['requires']['packages'] ?? [])->toBe($dependencies)
        ->and(class_exists($manifest['provider']))->toBeTrue()
        ->and(is_subclass_of($manifest['provider'], ServiceProvider::class))->toBeTrue();
});
