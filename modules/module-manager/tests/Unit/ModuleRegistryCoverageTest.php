<?php

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ModuleManager\Exceptions\DependencyResolutionFailed;
use Liberu\Foundation\ModuleManager\Exceptions\InvalidManifest;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

function makeCoverageManifest(array $overrides = [], ?string $package = null): Manifest
{
    $dir = sys_get_temp_dir().'/liberu-module-'.bin2hex(random_bytes(5));
    mkdir($dir, 0777, true);
    $data = array_replace([
        'name' => 'example', 'display_name' => 'Example', 'description' => 'Test',
        'version' => '1.0.0', 'category' => 'capability',
        'provider' => ServiceProvider::class,
        'requires' => ['packages' => [], 'capabilities' => []],
        'capabilities' => [], 'default_enabled' => true,
    ], $overrides);
    file_put_contents($dir.'/module.json', json_encode($data, JSON_THROW_ON_ERROR));
    if ($package) {
        file_put_contents($dir.'/composer.json', json_encode(['name' => $package], JSON_THROW_ON_ERROR));
    }

    return Manifest::fromFile($dir.'/module.json');
}

it('exposes and normalizes all manifest metadata', function () {
    $manifest = makeCoverageManifest([
        'display_name' => 'Covered', 'category' => 'presentation',
        'requires' => ['packages' => 'bad', 'capabilities' => 'bad', 'php' => '^8.5', 'laravel' => '^12.0'],
        'presentation' => ['filament' => ['admin' => [ServiceProvider::class, 42]]],
    ]);

    expect($manifest->displayName())->toBe('Covered')
        ->and($manifest->category())->toBe('presentation')
        ->and($manifest->provider())->toBe(ServiceProvider::class)
        ->and($manifest->defaultEnabled())->toBeTrue()
        ->and($manifest->requiredPackages())->toBe([])
        ->and($manifest->requiredCapabilities())->toBe([])
        ->and($manifest->phpConstraint())->toBe('^8.5')
        ->and($manifest->laravelConstraint())->toBe('^12.0')
        ->and($manifest->filamentPlugins('admin'))->toBe([ServiceProvider::class])
        ->and($manifest->filamentPlugins('app'))->toBe([])
        ->and($manifest->toArray()['path'])->toBe($manifest->path);
});

it('rejects malformed manifests', function (array $mutation, string $message) {
    $data = [
        'name' => 'example', 'display_name' => 'Example', 'description' => 'Test',
        'version' => '1.0.0', 'category' => 'capability',
        'provider' => ServiceProvider::class,
        'requires' => ['packages' => [], 'capabilities' => []],
        'capabilities' => [], 'default_enabled' => true,
    ];
    foreach ($mutation as $key => $value) {
        if ($value === null) {
            unset($data[$key]);
        } else {
            $data[$key] = $value;
        }
    }
    $dir = sys_get_temp_dir().'/liberu-invalid-'.bin2hex(random_bytes(5));
    mkdir($dir, 0777, true);
    file_put_contents($dir.'/module.json', json_encode($data, JSON_THROW_ON_ERROR));
    expect(fn () => Manifest::fromFile($dir.'/module.json'))->toThrow(InvalidManifest::class, $message);
})->with([
    'missing key' => [['description' => null], 'missing required key'],
    'name' => [['name' => 'Bad Name'], 'invalid module name'],
    'metadata' => [['requires' => 'bad'], 'invalid requires or capabilities'],
    'category' => [['category' => 'unknown'], 'invalid category'],
    'default' => [['default_enabled' => 1], 'default_enabled must be boolean'],
    'capability' => [['capabilities' => ['Bad capability']], 'invalid capability'],
]);

it('orders dependencies and exposes lookup and enabled state', function () {
    $foundation = makeCoverageManifest(['name' => 'foundation', 'capabilities' => ['foundation.ready']], 'local/foundation');
    $feature = makeCoverageManifest(['name' => 'feature', 'requires' => [
        'packages' => ['local/foundation' => '^1.0'], 'capabilities' => ['foundation.ready' => '^1.0'],
    ]], 'local/feature');
    $registry = new ModuleRegistry(['feature' => $feature, 'foundation' => $foundation]);
    expect($registry->has('feature'))->toBeTrue()->and($registry->has('absent'))->toBeFalse()
        ->and($registry->get('feature'))->toBe($feature)->and($registry->get('absent'))->toBeNull()
        ->and(array_map(fn (Manifest $item) => $item->name(), $registry->resolve([])))->toBe(['foundation', 'feature'])
        ->and($registry->enabled('feature'))->toBeTrue()
        ->and($registry->enabled('feature', [], ['feature']))->toBeFalse();
});

it('rejects invalid dependency graphs', function (array $modules, string $message) {
    $modules = array_combine(array_map(fn (Manifest $manifest) => $manifest->name(), $modules), $modules);
    expect(fn () => (new ModuleRegistry($modules))->resolve([]))->toThrow(DependencyResolutionFailed::class, $message);
})->with([
    'missing package' => fn () => [[
        'consumer' => makeCoverageManifest(['name' => 'consumer', 'requires' => ['packages' => ['missing/package' => '^1.0'], 'capabilities' => []]]),
    ], 'missing or incompatible library'],
    'incompatible package' => fn () => [[
        'owner' => makeCoverageManifest(['name' => 'owner', 'version' => '2.0.0'], 'local/owner'),
        'consumer' => makeCoverageManifest(['name' => 'consumer', 'requires' => ['packages' => ['local/owner' => '^1.0'], 'capabilities' => []]]),
    ], 'installed manifest is 2.0.0'],
    'disabled package' => fn () => [[
        'owner' => makeCoverageManifest(['name' => 'owner', 'default_enabled' => false], 'local/owner'),
        'consumer' => makeCoverageManifest(['name' => 'consumer', 'requires' => ['packages' => ['local/owner' => '^1.0'], 'capabilities' => []]]),
    ], 'requires enabled package'],
    'missing capability' => fn () => [[
        'consumer' => makeCoverageManifest(['name' => 'consumer', 'requires' => ['packages' => [], 'capabilities' => ['missing' => '^1.0']]]),
    ], 'requires missing capability'],
    'cycle' => fn () => [[
        'one' => makeCoverageManifest(['name' => 'one', 'requires' => ['packages' => ['local/two' => '^1.0'], 'capabilities' => []]], 'local/one'),
        'two' => makeCoverageManifest(['name' => 'two', 'requires' => ['packages' => ['local/one' => '^1.0'], 'capabilities' => []]], 'local/two'),
    ], 'Circular module dependency'],
]);
