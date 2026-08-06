<?php

use Liberu\Foundation\ModuleManager\Exceptions\DependencyResolutionFailed;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

/*
 * What is left here is the whole-graph rules: the ones that read every package at
 * once and so cannot be run by any single package. Everything a package can check
 * about itself moved to liberusoftware/package-testbench's boundary suites (§3.8),
 * where the repository that owns the fault is the one that goes red.
 *
 * Adding a rule here means first asking whether the package could run it alone.
 */

function moduleDirectories(): array
{
    return array_values(array_filter(glob(dirname(__DIR__, 2).'/modules/*') ?: [], 'is_dir'));
}

/**
 * The Composer vendor a package publishes under, read from the package itself.
 *
 * Every rule that filters requires down to "ours" needs this prefix, and none of
 * them may hardcode it: the prototype for §3.2 showed a literal prefix passing 44
 * packages under one vendor and failing 43 under the other, which makes the rule an
 * assertion about the spelling rather than about the boundary.
 */
function packageVendor(array $composer): string
{
    return explode('/', $composer['name'], 2)[0];
}

function modulePhpFiles(string $module): array
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module.'/src'));

    return array_values(array_filter(iterator_to_array($iterator), fn (SplFileInfo $file) => $file->isFile() && $file->getExtension() === 'php'));
}

it('lets every package install standalone', function () {
    $root = dirname(__DIR__, 2);
    $packageFiles = array_merge(glob($root.'/modules/*/composer.json') ?: [], glob($root.'/themes/*/composer.json') ?: []);

    expect($packageFiles)->not->toBeEmpty();

    foreach ($packageFiles as $packageFile) {
        $package = json_decode(file_get_contents($packageFile), true, flags: JSON_THROW_ON_ERROR);

        if (($package['config']['allow-plugins']['pestphp/pest-plugin'] ?? null) !== true) {
            throw new RuntimeException("{$package['name']} must allow pestphp/pest-plugin, or its standalone composer update aborts before installing Pest.");
        }
    }
});

it('derives enablement from the manifests, not a list in config', function () {
    // The previous rule diffed installed modules against two literal lists in
    // config/modules.php. Those lists are gone: a manifest's own default_enabled is
    // what boots it, so the accounting is between the manifests and what actually
    // resolved — and config may only ever hold deployment overrides.
    $config = require dirname(__DIR__, 2).'/config/modules.php';

    expect($config['enabled'])->toBe([], 'config/modules.php must not name modules; MODULES_ENABLED is the only lever.')
        ->and($config['disabled'])->toBe([], 'config/modules.php must not name modules; MODULES_DISABLED is the only lever.');

    $expected = [];
    foreach (moduleDirectories() as $module) {
        if (Manifest::fromFile($module.'/module.json')->defaultEnabled()) {
            $expected[] = basename($module);
        }
    }

    $resolved = array_map(
        fn (Manifest $manifest): string => $manifest->name(),
        app(ModuleRegistry::class)->resolve([], []),
    );

    sort($expected);
    sort($resolved);

    expect($resolved)->toBe($expected)
        ->and($expected)->not->toBeEmpty();
});

it('lets an override turn a module on and off again', function () {
    // Both levers, asserted rather than assumed: MODULES_ENABLED reaches a module its
    // manifest leaves off, and MODULES_DISABLED beats both the manifest and that.
    $registry = app(ModuleRegistry::class);
    $off = 'analytics-google';

    expect(Manifest::fromFile(dirname(__DIR__, 2)."/modules/{$off}/module.json")->defaultEnabled())->toBeFalse();

    $names = fn (array $enabled, array $disabled): array => array_map(
        fn (Manifest $manifest): string => $manifest->name(),
        $registry->resolve($enabled, $disabled),
    );

    expect($names([], []))->not->toContain($off)
        ->and($names([$off], []))->toContain($off)
        ->and($names([$off], [$off]))->not->toContain($off)
        // A leaf, because disabling a module something else requires is a resolution
        // failure rather than a quiet omission — which is the next assertion.
        ->and($names([], ['identity-core-filament']))->not->toContain('identity-core-filament');

    expect(fn () => $registry->resolve([], ['search']))
        ->toThrow(DependencyResolutionFailed::class, 'Module [search-api] requires enabled package [liberusoftware/search ^1.0].');
});

it('resolves every declared theme parent', function () {
    $themes = [];
    foreach (glob(dirname(__DIR__, 2).'/themes/*/theme.json') ?: [] as $manifestFile) {
        $manifest = json_decode(file_get_contents($manifestFile), true, flags: JSON_THROW_ON_ERROR);
        $themes[$manifest['name']] = $manifest['parent'] ?? '';
    }

    expect($themes)->not->toBeEmpty();

    foreach ($themes as $name => $parent) {
        if ($parent === '') {
            continue;
        }
        if (! array_key_exists($parent, $themes)) {
            throw new RuntimeException("Theme [{$name}] inherits from missing theme [{$parent}].");
        }

        expect($parent)->not->toBe($name);
    }
});

it('lets Composer own every module and theme autoload boundary', function () {
    $root = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect(array_filter(array_keys($composer['autoload']['psr-4']), fn (string $namespace) => str_starts_with($namespace, 'Liberu\\')))->toBe([]);

    foreach (array_merge(glob($root.'/modules/*/composer.json') ?: [], glob($root.'/themes/*/composer.json') ?: []) as $packageFile) {
        $package = json_decode(file_get_contents($packageFile), true, flags: JSON_THROW_ON_ERROR);
        expect($composer['require'])->toHaveKey($package['name']);
    }
});

it('publishes every package under one Composer vendor', function () {
    $root = dirname(__DIR__, 2);
    $packageFiles = array_merge(glob($root.'/modules/*/composer.json') ?: [], glob($root.'/themes/*/composer.json') ?: []);

    $vendors = [];
    $packages = [];
    foreach ($packageFiles as $packageFile) {
        $composer = json_decode(file_get_contents($packageFile), true, flags: JSON_THROW_ON_ERROR);
        $vendors[packageVendor($composer)] = true;
        $packages[$composer['name']] = true;
    }

    expect(array_keys($vendors))->toHaveCount(1, 'A split fleet cannot be filtered by one derived prefix: '.implode(', ', array_keys($vendors)));

    // Nothing may require a sibling under a different vendor. §3.2 keeps `liberusoftware/`
    // against the documentation's `liberu/`, and a stray require under the other spelling
    // resolves to a package nobody in this fleet publishes.
    $vendor = array_key_first($vendors);
    foreach ($packageFiles as $packageFile) {
        $composer = json_decode(file_get_contents($packageFile), true, flags: JSON_THROW_ON_ERROR);
        foreach (array_keys($composer['require'] ?? []) as $required) {
            if (isset($packages[$required]) && ! str_starts_with($required, $vendor.'/')) {
                throw new RuntimeException("{$composer['name']} requires {$required}, which is in this fleet but not under {$vendor}/.");
            }
        }
    }
});

it('declares a Filament plugin class that exists for every panel', function () {
    // Uniqueness of plugin ids is *not* asserted here. ModulePlugins rejects a duplicate
    // while composing the panels, which happens when this suite boots the application —
    // so a static rule could never be the thing that catches one, and would read as
    // coverage it does not provide. The guard itself is covered in
    // tests/Unit/ModuleFilamentPluginsTest.php, where it can actually fail.
    $declared = 0;
    foreach (moduleDirectories() as $module) {
        $manifest = json_decode(file_get_contents($module.'/module.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach ($manifest['presentation']['filament'] ?? [] as $panel => $plugins) {
            expect($panel)->toBeIn(['admin', 'app']);

            foreach ((array) $plugins as $plugin) {
                expect(class_exists($plugin))->toBeTrue("{$manifest['name']} declares {$plugin} for the {$panel} panel, which does not exist.");
                $declared++;
            }
        }
    }

    expect($declared)->toBeGreaterThan(0);
});

it('declares every cross-package Liberu namespace dependency in Composer', function () {
    $root = dirname(__DIR__, 2);
    $vendor = packageVendor(json_decode(file_get_contents($root.'/modules/module-manager/composer.json'), true, flags: JSON_THROW_ON_ERROR));
    $packageFiles = array_merge(
        glob($root.'/modules/*/composer.json') ?: [],
        glob($root.'/themes/*/composer.json') ?: [],
        glob($root.'/vendor/'.$vendor.'/*/composer.json') ?: [],
    );
    $packages = [];
    foreach ($packageFiles as $composerFile) {
        $composer = json_decode(file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
        foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $path) {
            $packages[rtrim($namespace, '\\').'\\'] = ['name' => $composer['name'], 'file' => $composerFile];
        }
    }
    uksort($packages, fn (string $left, string $right): int => strlen($right) <=> strlen($left));

    foreach (glob($root.'/modules/*/composer.json') ?: [] as $composerFile) {
        $composer = json_decode(file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
        $module = dirname($composerFile);
        foreach (modulePhpFiles($module) as $file) {
            preg_match_all('/^use\s+(Liberu\\\\[^;]+);/m', file_get_contents($file->getPathname()), $matches);
            foreach ($matches[1] as $import) {
                $owner = collect($packages)->first(fn (array $package, string $namespace) => str_starts_with($import.'\\', $namespace));
                if ($owner === null || $owner['name'] === $composer['name']) {
                    continue;
                }
                if (! array_key_exists($owner['name'], $composer['require'] ?? [])) {
                    throw new RuntimeException("{$composer['name']} imports {$import} without requiring {$owner['name']} ({$file->getPathname()}).");
                }
            }
        }
    }

    expect(true)->toBeTrue();
});
