<?php

use Illuminate\Filesystem\Filesystem;
use Liberu\Foundation\Theme\Cache\ThemeCache;
use Liberu\Foundation\Theme\Discovery\ThemeDiscovery;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\Theme\Manifests\ThemeManifest;
use Tests\TestCase;

uses(TestCase::class);

function writeCoverageTheme(array $changes = [], bool $asset = true): string
{
    $dir = sys_get_temp_dir().'/liberu-theme-'.bin2hex(random_bytes(5));
    mkdir($dir, 0777, true);
    if ($asset) {
        file_put_contents($dir.'/theme.css', '');
    }
    $data = array_replace([
        'name' => 'covered', 'display_name' => 'Covered', 'version' => '1.0.0',
        'provider' => TestCase::class, 'type' => 'shared', 'parent' => '',
        'optimized_for' => [], 'tested_with' => [], 'required_capabilities' => ['one'],
        'optional_capabilities' => ['two'], 'supports' => [],
        'assets' => ['css' => ['theme.css'], 'js' => []],
    ], $changes);
    foreach ($changes as $key => $value) {
        if ($value === null) {
            unset($data[$key]);
        }
    }
    file_put_contents($dir.'/theme.json', json_encode($data, JSON_THROW_ON_ERROR));

    return $dir.'/theme.json';
}

it('exposes all theme manifest values', function () {
    $manifest = ThemeManifest::fromFile(writeCoverageTheme(['parent' => 'base']));
    expect($manifest->name())->toBe('covered')->and($manifest->displayName())->toBe('Covered')
        ->and($manifest->version())->toBe('1.0.0')->and($manifest->provider())->toBe(TestCase::class)
        ->and($manifest->type())->toBe('shared')->and($manifest->parent())->toBe('base')
        ->and($manifest->assets('css'))->toBe(['theme.css'])->and($manifest->assets('unknown'))->toBe([])
        ->and($manifest->requiredCapabilities())->toBe(['one'])->and($manifest->optionalCapabilities())->toBe(['two'])
        ->and($manifest->toArray())->toHaveKeys(['label', 'path']);
});

it('rejects malformed theme discovery directories', function (Closure $arrange, string $message) {
    $root = sys_get_temp_dir().'/liberu-themes-'.bin2hex(random_bytes(5));
    mkdir($root, 0777, true);
    $arrange($root);
    expect(fn () => (new ThemeDiscovery())->discover($root))->toThrow(InvalidTheme::class, $message);
})->with([
    'name collision' => [function (string $root) {
        $source = dirname(writeCoverageTheme());
        rename($source, $root.'/different');
        file_put_contents($root.'/different/composer.json', json_encode(['type' => 'liberu-theme', 'extra' => ['liberu' => ['name' => 'covered']]]));
    }, 'directory/name collision'],
    'missing composer' => [function (string $root) {
        $source = dirname(writeCoverageTheme(['name' => 'covered']));
        rename($source, $root.'/covered');
    }, 'has no composer.json'],
    'bad composer' => [function (string $root) {
        $source = dirname(writeCoverageTheme(['name' => 'covered']));
        rename($source, $root.'/covered');
        file_put_contents($root.'/covered/composer.json', '{}');
    }, 'metadata is inconsistent'],
    'missing provider' => [function (string $root) {
        $source = dirname(writeCoverageTheme(['name' => 'covered', 'provider' => 'Missing\\ThemeProvider']));
        rename($source, $root.'/covered');
        file_put_contents($root.'/covered/composer.json', json_encode(['type' => 'liberu-theme', 'extra' => ['liberu' => ['name' => 'covered']]]));
    }, 'not autoloadable'],
]);

it('rejects a missing themes directory', function () {
    expect(fn () => (new ThemeDiscovery())->discover(sys_get_temp_dir().'/absent-'.bin2hex(random_bytes(5))))
        ->toThrow(InvalidTheme::class, 'tracked themes directory is missing');
});

it('ignores tracked directories that do not contain a theme manifest', function () {
    $root = sys_get_temp_dir().'/themes-with-notes-'.bin2hex(random_bytes(5));
    mkdir($root.'/notes', 0777, true);

    expect((new ThemeDiscovery())->discover($root))->toBe([]);
});

it('reports theme cache filesystem write and delete failures', function () {
    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('put')->andReturnFalse();
    expect(fn () => (new ThemeCache($files))->write([], '/cache/themes'))
        ->toThrow(InvalidTheme::class, 'Unable to write');

    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('isFile')->andReturnTrue();
    $files->shouldReceive('delete')->andReturnFalse();
    expect(fn () => (new ThemeCache($files))->clear('/cache/themes'))
        ->toThrow(InvalidTheme::class, 'Unable to clear');
});

it('rejects invalid theme manifests', function (array $changes, string $message, bool $asset = true) {
    expect(fn () => ThemeManifest::fromFile(writeCoverageTheme($changes, $asset)))->toThrow(InvalidTheme::class, $message);
})->with([
    'missing field' => [['supports' => null], ''],
    'bad name' => [['name' => 'Bad Name'], 'Theme name is invalid'],
    'bad type' => [['type' => 'email'], 'invalid type'],
    'absolute asset' => [['assets' => ['css' => ['/theme.css']]], 'invalid css asset'],
    'traversal asset' => [['assets' => ['css' => ['../theme.css']]], 'invalid css asset'],
    'non string asset' => [['assets' => ['css' => [42]]], 'invalid css asset'],
    'missing asset' => [['assets' => ['css' => ['missing.css']]], 'invalid css asset', false],
]);
