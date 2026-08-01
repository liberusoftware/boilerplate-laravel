<?php

use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

function moduleDirectories(): array
{
    return array_values(array_filter(glob(dirname(__DIR__, 2).'/modules/*') ?: [], 'is_dir'));
}

function modulePhpFiles(string $module): array
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module.'/src'));

    return array_values(array_filter(iterator_to_array($iterator), fn (SplFileInfo $file) => $file->isFile()
        && $file->getExtension() === 'php'
        && ! str_contains($file->getPathname(), '/vendor/')));
}

function allModulePhpFiles(string $module): array
{
    $directories = new RecursiveDirectoryIterator($module);
    $filtered = new RecursiveCallbackFilterIterator(
        $directories,
        fn (SplFileInfo $file) => ! ($file->isDir() && $file->getFilename() === 'vendor'),
    );
    $iterator = new RecursiveIteratorIterator($filtered);

    return array_values(array_filter(iterator_to_array($iterator), fn (SplFileInfo $file) => $file->isFile()
        && $file->getExtension() === 'php'));
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
    }
});

it('requires every module to exercise its service provider in the application', function () {
    foreach (moduleDirectories() as $module) {
        $test = $module.'/tests/Integration/ServiceProviderTest.php';

        expect($test)->toBeFile()
            ->and(file_get_contents($test))->toContain('getProvider(');
    }
});

it('prevents every module PHP boundary from referencing the host application', function () {
    foreach (moduleDirectories() as $module) {
        foreach (allModulePhpFiles($module) as $file) {
            expect(file_get_contents($file->getPathname()))->not->toContain('App\\');
        }
    }
});

it('autoloads each module test namespace in its standalone package', function () {
    foreach (moduleDirectories() as $module) {
        $composer = json_decode(file_get_contents($module.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $sourceNamespace = array_key_first($composer['autoload']['psr-4']);
        $testNamespace = $sourceNamespace.'Tests\\';
        $integrationTest = $module.'/tests/Integration/ServiceProviderTest.php';

        expect($composer['autoload-dev']['psr-4'][$testNamespace] ?? null)->toBe('tests/')
            ->and($composer['require-dev']['orchestra/testbench'] ?? null)->toBe('^11.1')
            ->and($composer['require-dev']['pestphp/pest'] ?? null)->toBe('^5.0')
            ->and($module.'/phpunit.xml')->toBeFile()
            ->and($module.'/.github/workflows/tests.yml')->toBeFile()
            ->and(file_get_contents($integrationTest))->toContain("namespace {$testNamespace}Integration;");
    }
});

it('classifies every installed module as enabled or explicitly disabled', function () {
    $installed = array_map('basename', moduleDirectories());
    $enabled = config('modules.available.enabled', []);
    $disabled = config('modules.available.disabled', []);
    sort($installed);
    sort($enabled);
    sort($disabled);
    $classified = array_values(array_unique(array_merge($enabled, $disabled)));
    sort($classified);

    expect(array_intersect($enabled, $disabled))->toBe([])
        ->and($classified)->toBe($installed)
        ->and($disabled)->toContain('analytics-google', 'analytics-meta', 'localization-mymemory', 'search-demo');
});

it('lists every installed module explicitly in the host PHPUnit suites', function () {
    $root = dirname(__DIR__, 2);
    $xml = simplexml_load_file($root.'/phpunit.xml');
    $expectedTests = $expectedSources = [];
    foreach (moduleDirectories() as $module) {
        $name = basename($module);
        $expectedTests[] = "modules/{$name}/tests";
        $expectedSources[] = "modules/{$name}/src";
    }
    $actualTests = array_map('strval', $xml->xpath('//testsuite[@name="Modules"]/directory'));
    $actualSources = array_map('strval', $xml->xpath('//source/include/directory[starts-with(text(), "modules/")]'));
    sort($expectedTests);
    sort($expectedSources);
    sort($actualTests);
    sort($actualSources);

    expect($actualTests)->toBe($expectedTests)
        ->and($actualSources)->toBe($expectedSources);
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

it('lets Composer own every module and theme autoload boundary', function () {
    $root = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect(array_filter(array_keys($composer['autoload']['psr-4']), fn (string $namespace) => str_starts_with($namespace, 'Liberu\\')))->toBe([]);

    foreach (array_merge(glob($root.'/modules/*/composer.json') ?: [], glob($root.'/themes/*/composer.json') ?: []) as $packageFile) {
        $package = json_decode(file_get_contents($packageFile), true, flags: JSON_THROW_ON_ERROR);
        expect(array_merge($composer['require'], $composer['require-dev']))->toHaveKey($package['name']);
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
