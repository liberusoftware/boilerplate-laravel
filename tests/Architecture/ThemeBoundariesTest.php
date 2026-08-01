<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Theme\Services\ThemeManager;
use Tests\TestCase;

uses(TestCase::class);

function themeManifests(): array
{
    $manifests = [];
    foreach (glob(dirname(__DIR__, 2).'/themes/*/theme.json') ?: [] as $path) {
        $manifest = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $manifests[$manifest['name']] = ['path' => $path, 'manifest' => $manifest];
    }

    return $manifests;
}

it('gives every theme complete metadata and an autoloadable provider', function () {
    $required = [
        'name', 'display_name', 'version', 'provider', 'type', 'parent',
        'optimized_for', 'tested_with', 'required_capabilities',
        'optional_capabilities', 'supports', 'assets',
    ];

    foreach (themeManifests() as $name => $theme) {
        $directory = dirname($theme['path']);
        $manifest = $theme['manifest'];
        $composer = json_decode(file_get_contents($directory.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

        expect(basename($directory))->toBe($name)
            ->and($directory.'/composer.json')->toBeFile()
            ->and($directory.'/README.md')->toBeFile()
            ->and($directory.'/LICENSE.md')->toBeFile()
            ->and($directory.'/CHANGELOG.md')->toBeFile()
            ->and($manifest)->toHaveKeys($required)
            ->and($composer['type'] ?? null)->toBe('liberu-theme')
            ->and($composer['version'] ?? null)->toBe($manifest['version'])
            ->and($composer['extra']['liberu']['name'] ?? null)->toBe($name)
            ->and(class_exists($manifest['provider']))->toBeTrue()
            ->and(is_subclass_of($manifest['provider'], ServiceProvider::class))->toBeTrue();

        foreach (['css', 'js'] as $kind) {
            foreach ($manifest['assets'][$kind] ?? [] as $asset) {
                expect($directory.'/'.$asset)->toBeFile();
            }
        }
    }
});

it('resolves every parent and rejects cycles in the installed theme graph', function () {
    $themes = themeManifests();

    foreach ($themes as $name => $theme) {
        $seen = [];
        while ($name !== null) {
            expect($themes)->toHaveKey($name);
            expect($seen)->not->toHaveKey($name);
            $seen[$name] = true;
            $parent = $themes[$name]['manifest']['parent'];
            $name = is_string($parent) && $parent !== '' ? $parent : null;
        }
    }
});

it('renders a child theme layout from its parent when the child has no layout', function () {
    $manager = app(ThemeManager::class);
    $manager->setTheme('clear-signal');
    $bufferLevel = ob_get_level();

    try {
        $rendered = Blade::render("@extends('layouts.app') @section('content')Inherited theme layout@endsection");
    } finally {
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }
    }

    expect($manager->getThemeViewsPath('clear-signal').'/layouts/app.blade.php')->not->toBeFile()
        ->and($rendered)
        ->toContain('Inherited theme layout')
        ->toContain('theme-shell');
});
