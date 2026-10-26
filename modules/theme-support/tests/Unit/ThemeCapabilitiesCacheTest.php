<?php

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ModuleManager\Exceptions\DependencyResolutionFailed;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\Theme\Manifests\ThemeManifest;
use Liberu\Foundation\Theme\Services\ThemeManager;

function capabilitySnapshotManifest(string $name, array $requires = []): Manifest
{
    $directory = sys_get_temp_dir().'/theme-capabilities-'.bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    file_put_contents($directory.'/composer.json', json_encode(['name' => 'local/theme-'.$name], JSON_THROW_ON_ERROR));
    file_put_contents($directory.'/module.json', json_encode([
        'name' => $name, 'display_name' => $name, 'description' => 'Capability snapshot fixture',
        'version' => '1.0.0', 'category' => 'foundation', 'provider' => ServiceProvider::class,
        'requires' => ['packages' => $requires, 'capabilities' => []],
        'capabilities' => ['fixture.'.$name], 'features' => ['Fixture'], 'default_enabled' => true,
    ], JSON_THROW_ON_ERROR));

    return Manifest::fromFile($directory.'/module.json');
}

it('reuses capability snapshots while refreshing them for configuration or registry changes', function (): void {
    $foundation = capabilitySnapshotManifest('foundation');
    $consumer = capabilitySnapshotManifest('consumer', ['local/theme-foundation' => '^1.0']);
    $registry = new ModuleRegistry(['foundation' => $foundation, 'consumer' => $consumer]);
    $this->app->instance(ModuleRegistry::class, $registry);
    config(['modules.enabled' => [], 'modules.disabled' => []]);
    $manager = new ThemeManager;
    $themePath = $foundation->path.'/fixture-theme.json';
    file_put_contents($themePath, json_encode([
        'name' => 'fixture-theme', 'display_name' => 'Fixture', 'version' => '1.0.0',
        'provider' => ServiceProvider::class, 'type' => 'public', 'parent' => null,
        'optimized_for' => [], 'tested_with' => [], 'required_capabilities' => ['fixture.foundation'],
        'optional_capabilities' => [], 'supports' => [], 'assets' => ['css' => [], 'js' => []],
    ], JSON_THROW_ON_ERROR));
    $themes = new ReflectionProperty(ThemeManager::class, 'themes');
    $themes->setValue($manager, ['fixture-theme' => ThemeManifest::fromFile($themePath)]);
    expect($manager->themeExists('fixture-theme'))->toBeTrue();
    $initial = $manager->themeIsCompatible('fixture-theme');

    $path = $foundation->path.'/composer.json';
    $metadata = file_get_contents($path);
    unlink($path);
    try {
        expect($manager->themeIsCompatible('fixture-theme'))->toBe($initial);
    } finally {
        file_put_contents($path, $metadata);
    }

    config(['modules.disabled' => ['foundation']]);
    expect(fn () => $manager->themeIsCompatible('fixture-theme'))->toThrow(DependencyResolutionFailed::class);

    config(['modules.disabled' => []]);
    $this->app->instance(ModuleRegistry::class, new ModuleRegistry(['consumer' => $consumer]));
    expect(fn () => $manager->themeIsCompatible('fixture-theme'))->toThrow(DependencyResolutionFailed::class);
});
