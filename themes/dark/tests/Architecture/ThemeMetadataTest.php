<?php

use Illuminate\Support\ServiceProvider;

it('exposes internally consistent package metadata', function () {
    $theme = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($theme.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(file_get_contents($theme.'/theme.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['type'])->toBe('liberu-theme')
        ->and($composer['version'])->toBe($manifest['version'])
        ->and($composer['extra']['liberu']['name'])->toBe($manifest['name'])
        ->and(class_exists($manifest['provider']))->toBeTrue()
        ->and(is_subclass_of($manifest['provider'], ServiceProvider::class))->toBeTrue();
});

it('ships every asset it declares', function () {
    $theme = dirname(__DIR__, 2);
    $manifest = json_decode(file_get_contents($theme.'/theme.json'), true, flags: JSON_THROW_ON_ERROR);
    $assets = array_merge($manifest['assets']['css'] ?? [], $manifest['assets']['js'] ?? []);

    expect($assets)->not->toBeEmpty();

    foreach ($assets as $asset) {
        expect($theme.'/'.$asset)->toBeFile();
    }
});
