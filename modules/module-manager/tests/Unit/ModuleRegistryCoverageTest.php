<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ModuleManager\Cache\RegistryCache;
use Liberu\Foundation\ModuleManager\Exceptions\DependencyResolutionFailed;
use Liberu\Foundation\ModuleManager\Exceptions\InvalidManifest;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleDiscovery;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\ModuleManager\ModuleValidator;
use Liberu\PackageTestbench\PackageTestCase as TestCase;

function makeCoverageManifest(array $overrides = [], ?string $package = null): Manifest
{
    $dir = sys_get_temp_dir().'/liberu-module-'.bin2hex(random_bytes(5));
    mkdir($dir, 0777, true);
    $data = array_replace([
        'name' => 'example', 'display_name' => 'Example', 'description' => 'Test',
        'version' => '1.0.0', 'category' => 'capability',
        'provider' => ServiceProvider::class,
        'requires' => ['packages' => [], 'capabilities' => []],
        'capabilities' => [], 'features' => ['Example feature'], 'default_enabled' => true,
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
        'presentation' => ['filament' => ['admin' => [TestCase::class, 42]]],
    ]);

    expect($manifest->displayName())->toBe('Covered')
        ->and($manifest->category())->toBe('presentation')
        ->and($manifest->provider())->toBe(ServiceProvider::class)
        ->and($manifest->defaultEnabled())->toBeTrue()
        ->and($manifest->features())->toBe(['Example feature'])
        ->and($manifest->requiredPackages())->toBe([])
        ->and($manifest->requiredCapabilities())->toBe([])
        ->and($manifest->phpConstraint())->toBe('^8.5')
        ->and($manifest->laravelConstraint())->toBe('^12.0')
        ->and($manifest->filamentPlugins('admin'))->toBe([TestCase::class])
        ->and($manifest->filamentPlugins('app'))->toBe([])
        ->and($manifest->toArray()['path'])->toBe($manifest->path);
});

it('rejects malformed manifests', function (array $mutation, string $message) {
    $data = [
        'name' => 'example', 'display_name' => 'Example', 'description' => 'Test',
        'version' => '1.0.0', 'category' => 'capability',
        'provider' => ServiceProvider::class,
        'requires' => ['packages' => [], 'capabilities' => []],
        'capabilities' => [], 'features' => ['Example feature'], 'default_enabled' => true,
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
    'metadata' => [['requires' => 'bad'], 'invalid requires, capabilities, or features'],
    'category' => [['category' => 'unknown'], 'invalid category'],
    'default' => [['default_enabled' => 1], 'default_enabled must be boolean'],
    'capability' => [['capabilities' => ['Bad capability']], 'invalid capability'],
    'missing features' => [['features' => null], 'missing required key'],
    'features metadata' => [['features' => 'bad'], 'invalid requires, capabilities, or features'],
    'empty features' => [['features' => []], 'at least one feature'],
    'invalid feature' => [['features' => [' feature']], 'invalid feature'],
    'long feature' => [['features' => [str_repeat('x', 121)]], 'invalid feature'],
    'duplicate feature' => [['features' => ['Feature', 'feature']], 'duplicate features'],
]);

it('loads writes and clears a valid registry cache and rejects invalid cache data', function () {
    $cache = new RegistryCache();
    $path = sys_get_temp_dir().'/nested-'.bin2hex(random_bytes(5)).'/registry.cache';
    $registry = new ModuleRegistry([]);
    $cache->write($registry, $path);

    expect($cache->load([], true, $path))->toEqual($registry);
    $cache->clear($path);
    expect(is_file($path))->toBeFalse();

    file_put_contents($path, serialize('invalid'));
    expect(fn () => $cache->load([], true, $path))->toThrow(RuntimeException::class, 'cache is invalid');
});

it('reports registry cache filesystem write and delete failures', function () {
    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('isDirectory')->andReturnTrue();
    $files->shouldReceive('put')->andReturnFalse();
    $cache = new RegistryCache($files);
    expect(fn () => $cache->write(new ModuleRegistry([]), '/cache/registry'))->toThrow(RuntimeException::class, 'Unable to write');

    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('isFile')->andReturnTrue();
    $files->shouldReceive('delete')->andReturnFalse();
    expect(fn () => (new RegistryCache($files))->clear('/cache/registry'))->toThrow(RuntimeException::class, 'Unable to clear');
});

it('rejects every invalid module discovery collision', function (Closure $arrange, string $message) {
    $root = sys_get_temp_dir().'/discovery-'.bin2hex(random_bytes(5));
    mkdir($root, 0777, true);
    $arrange($root);
    expect(fn () => (new ModuleDiscovery())->discover([$root]))->toThrow(InvalidManifest::class, $message);
})->with([
    'missing composer' => [function (string $root) {
        $manifest = makeCoverageManifest(['name' => 'one']);
        rename($manifest->path, $root.'/one');
    }, 'has no composer.json'],
    'missing package name' => [function (string $root) {
        $manifest = makeCoverageManifest(['name' => 'one'], 'local/one');
        rename($manifest->path, $root.'/one');
        file_put_contents($root.'/one/composer.json', '{}');
    }, 'missing or duplicate Composer package name'],
    'duplicate module' => [function (string $root) {
        foreach (['first' => 'local/first', 'second' => 'local/second'] as $dir => $package) {
            $manifest = makeCoverageManifest(['name' => 'same'], $package);
            rename($manifest->path, $root.'/'.$dir);
        }
    }, 'Duplicate module'],
    'duplicate package' => [function (string $root) {
        foreach (['one', 'two'] as $name) {
            $manifest = makeCoverageManifest(['name' => $name], 'local/shared');
            rename($manifest->path, $root.'/'.$name);
        }
    }, 'missing or duplicate Composer package name'],
    'duplicate capability' => [function (string $root) {
        foreach (['one', 'two'] as $name) {
            $manifest = makeCoverageManifest(['name' => $name, 'capabilities' => ['shared']], 'local/'.$name);
            rename($manifest->path, $root.'/'.$name);
        }
    }, 'Duplicate capability'],
]);

it('reports all invalid module metadata and dependency errors', function () {
    $manifest = makeCoverageManifest([
        'name' => 'invalid',
        'version' => '999.0.0',
        'provider' => 'Missing\\Provider',
        'requires' => ['packages' => ['liberusoftware/missing' => '^1.0'], 'capabilities' => ['missing' => '^1.0'], 'php' => '>999', 'laravel' => '>999'],
        'presentation' => ['filament' => ['admin' => ['Missing\\Plugin']]],
    ], 'liberusoftware/missing-module');
    file_put_contents($manifest->path.'/composer.json', json_encode([
        'name' => 'liberusoftware/missing-module', 'type' => 'library',
        'extra' => ['liberu' => ['name' => 'different']], 'require' => [],
    ], JSON_THROW_ON_ERROR));

    $errors = (new ModuleValidator())->validate(new ModuleRegistry(['invalid' => $manifest]), '12.0.0');
    expect(implode("\n", $errors))->toContain(
        'does not match', 'type must be', 'not installed', 'dependencies differ',
        'not autoloadable', 'PHP', 'Laravel', 'only presentation',
        'presentation plugin',
    );
});

it('reports an installed Composer version mismatch and a non-provider class', function () {
    $manifest = makeCoverageManifest([
        'name' => 'module-manager', 'version' => '0.0.1', 'provider' => stdClass::class,
    ], 'liberusoftware/module-manager');
    file_put_contents($manifest->path.'/composer.json', json_encode([
        'name' => 'liberusoftware/module-manager', 'type' => 'liberu-module',
        'extra' => ['liberu' => ['name' => 'module-manager']], 'require' => [],
    ], JSON_THROW_ON_ERROR));

    expect(implode("\n", (new ModuleValidator())->validate(new ModuleRegistry(['module-manager' => $manifest]), '13.0.0')))
        ->toContain('installed Composer version does not match', 'provider must extend');
});

it('orders dependencies and exposes lookup and enabled state', function () {
    $foundation = makeCoverageManifest(['name' => 'foundation', 'capabilities' => ['foundation.ready'], 'features' => ['Health checks', 'Readiness']], 'local/foundation');
    $feature = makeCoverageManifest(['name' => 'feature', 'requires' => [
        'packages' => ['local/foundation' => '^1.0'], 'capabilities' => ['foundation.ready' => '^1.0'],
    ]], 'local/feature');
    $registry = new ModuleRegistry(['feature' => $feature, 'foundation' => $foundation]);
    expect($registry->has('feature'))->toBeTrue()->and($registry->has('absent'))->toBeFalse()
        ->and($registry->get('feature'))->toBe($feature)->and($registry->get('absent'))->toBeNull()
        ->and($registry->searchFeatures())->toHaveKeys(['feature', 'foundation'])
        ->and($registry->searchFeatures('health'))->toBe(['foundation' => ['Health checks']])
        ->and($registry->searchFeatures('missing'))->toBe([])
        ->and($registry->providingFeature(' readiness '))->toBe([$foundation])
        ->and($registry->providingFeature('missing'))->toBe([])
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

it('deduplicates identical installed and tracked module copies', function () {
    $roots = [];
    foreach (['one', 'two'] as $suffix) {
        $root = sys_get_temp_dir().'/duplicate-copy-'.$suffix.'-'.bin2hex(random_bytes(4));
        mkdir($root, 0777, true);
        $manifest = makeCoverageManifest(['name' => 'same'], 'local/same');
        rename($manifest->path, $root.'/same');
        $roots[] = $root;
    }

    expect((new ModuleDiscovery())->discover($roots)->has('same'))->toBeTrue();
});

it('rejects a disabled capability provider', function () {
    $owner = makeCoverageManifest(['name' => 'owner', 'default_enabled' => false, 'capabilities' => ['owned']], 'local/owner');
    $consumer = makeCoverageManifest(['name' => 'consumer', 'requires' => ['packages' => [], 'capabilities' => ['owned' => '^1.0']]], 'local/consumer');

    expect($owner->capabilities())->toBe(['owned']);
    expect(fn () => (new ModuleRegistry(['owner' => $owner, 'consumer' => $consumer]))->resolve([]))
        ->toThrow(DependencyResolutionFailed::class, 'requires enabled capability');
});
