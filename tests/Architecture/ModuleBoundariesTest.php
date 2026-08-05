<?php

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ModuleManager\Manifest;

function moduleDirectories(): array
{
    return array_values(array_filter(glob(dirname(__DIR__, 2).'/modules/*') ?: [], 'is_dir'));
}

function modulePhpFiles(string $module): array
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module.'/src'));

    return array_values(array_filter(iterator_to_array($iterator), fn (SplFileInfo $file) => $file->isFile() && $file->getExtension() === 'php'));
}

it('gives every runtime module complete package metadata', function () {
    foreach (moduleDirectories() as $module) {
        expect($module.'/composer.json')->toBeFile()
            ->and($module.'/module.json')->toBeFile()
            ->and($module.'/README.md')->toBeFile()
            ->and($module.'/LICENSE.md')->toBeFile()
            ->and($module.'/CHANGELOG.md')->toBeFile();

        $composer = json_decode(file_get_contents($module.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $manifest = json_decode(file_get_contents($module.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
        $moduleDependencies = array_filter($composer['require'] ?? [], fn ($constraint, $package) => str_starts_with($package, 'liberusoftware/'), ARRAY_FILTER_USE_BOTH);

        expect($composer['type'] ?? null)->toBe('liberu-module')
            ->and($composer['extra']['liberu']['name'] ?? null)->toBe($manifest['name'])
            ->and($composer['extra']['laravel']['providers'] ?? [])->toBe([])
            ->and($manifest['requires']['packages'] ?? [])->toBe($moduleDependencies)
            ->and(class_exists($manifest['provider']))->toBeTrue()
            ->and(is_subclass_of($manifest['provider'], ServiceProvider::class))->toBeTrue();

        // Every installed manifest must survive the canonical parser. Owning it here keeps
        // module-manager's own suite free of the consuming application's modules/ directory.
        expect(Manifest::fromFile($module.'/module.json')->version())->toMatch('/^\d+\.\d+\.\d+$/');
    }
});

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

it('accounts for every installed module in the runtime selection', function () {
    $installed = array_map('basename', moduleDirectories());
    $selected = array_merge((array) config('modules.enabled'), (array) config('modules.disabled'));

    expect(array_values(array_diff($installed, $selected)))->toBe([])
        ->and(array_values(array_diff($selected, $installed)))->toBe([]);
});

it('runs and measures every installed module', function () {
    $phpunit = file_get_contents(dirname(__DIR__, 2).'/phpunit.xml');
    $installed = array_map('basename', moduleDirectories());

    foreach (['#<directory>modules/([^/<]+)/src</directory>#', '#<directory suffix="Test.php">modules/([^/<]+)/tests</directory>#'] as $pattern) {
        preg_match_all($pattern, $phpunit, $configured);

        expect(array_values(array_diff($installed, $configured[1])))->toBe([])
            ->and(array_values(array_diff($configured[1], $installed)))->toBe([]);
    }
});

it('requires every module to exercise its service provider in the application', function () {
    foreach (moduleDirectories() as $module) {
        $test = $module.'/tests/Integration/ServiceProviderTest.php';

        expect($test)->toBeFile()
            ->and(file_get_contents($test))->toContain('->register($provider, true)');
    }
});

it('prevents modules from depending on the host application', function () {
    foreach (moduleDirectories() as $module) {
        foreach (modulePhpFiles($module) as $file) {
            expect(file_get_contents($file->getPathname()))
                ->not->toMatch('/(?:use|new|extends|implements)\s+App\\\\/');
        }
    }
});

it('keeps filament out of non-presentation modules', function () {
    foreach (moduleDirectories() as $module) {
        $manifest = json_decode(file_get_contents($module.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
        if ($manifest['category'] === 'presentation') {
            continue;
        }
        foreach (modulePhpFiles($module) as $file) {
            expect(file_get_contents($file->getPathname()))->not->toContain('Filament\\');
        }
    }
});

it('does not let api adapters import domain persistence models', function () {
    foreach (moduleDirectories() as $module) {
        if (! str_ends_with($module, '-api')) {
            continue;
        }
        foreach (modulePhpFiles($module) as $file) {
            expect(file_get_contents($file->getPathname()))->not->toMatch('/use Liberu\\\\.+\\\\Models\\\\/');
        }
    }
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

it('ships every asset a theme declares', function () {
    foreach (glob(dirname(__DIR__, 2).'/themes/*/theme.json') ?: [] as $manifestFile) {
        $manifest = json_decode(file_get_contents($manifestFile), true, flags: JSON_THROW_ON_ERROR);
        $assets = array_merge($manifest['assets']['css'] ?? [], $manifest['assets']['js'] ?? []);

        expect($assets)->not->toBeEmpty();

        foreach ($assets as $asset) {
            expect(dirname($manifestFile).'/'.$asset)->toBeFile();
        }
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

it('requires filament presentation modules to declare panel plugins', function () {
    foreach (moduleDirectories() as $module) {
        if (! str_ends_with($module, '-filament')) {
            continue;
        }
        $manifest = json_decode(file_get_contents($module.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
        expect($manifest['category'])->toBe('presentation')
            ->and($manifest['presentation']['filament'] ?? [])->not->toBeEmpty();
    }
});

it('declares every cross-package Liberu namespace dependency in Composer', function () {
    $root = dirname(__DIR__, 2);
    $packageFiles = array_merge(
        glob($root.'/modules/*/composer.json') ?: [],
        glob($root.'/themes/*/composer.json') ?: [],
        glob($root.'/vendor/liberusoftware/*/composer.json') ?: [],
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
