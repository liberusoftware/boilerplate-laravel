<?php

use Illuminate\Support\ServiceProvider;

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
            ->and($module.'/CHANGELOG.md')->toBeFile();

        $composer = json_decode(file_get_contents($module.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $manifest = json_decode(file_get_contents($module.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
        $moduleDependencies = array_filter($composer['require'] ?? [], fn ($constraint, $package) => str_starts_with($package, 'liberu/'), ARRAY_FILTER_USE_BOTH);

        expect($composer['type'] ?? null)->toBe('liberu-module')
            ->and($composer['extra']['liberu']['name'] ?? null)->toBe($manifest['name'])
            ->and($composer['extra']['laravel']['providers'] ?? [])->toBe([])
            ->and($manifest['requires']['packages'] ?? [])->toBe($moduleDependencies)
            ->and(class_exists($manifest['provider']))->toBeTrue()
            ->and(is_subclass_of($manifest['provider'], ServiceProvider::class))->toBeTrue();
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
        glob($root.'/packages/*/composer.json') ?: [],
        glob($root.'/themes/*/composer.json') ?: [],
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
