<?php

use Liberu\Foundation\ModuleManager\Exceptions\DependencyResolutionFailed;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleDiscovery;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

it('discovers canonical packages from the modules directory', function () {
    $registry = (new ModuleDiscovery())->discover([base_path('modules')]);

    expect($registry->has('module-manager'))->toBeTrue()
        ->and($registry->get('module-manager'))->toBeInstanceOf(Manifest::class)
        ->and($registry->get('module-manager')->capabilities())->toBe(['foundation.modules']);
});

it('resolves enabled modules in stable dependency order', function () {
    $registry = (new ModuleDiscovery())->discover([base_path('modules')]);
    $names = array_map(fn (Manifest $manifest) => $manifest->name(), $registry->resolve((array) config('modules.enabled', [])));

    expect($names)->toContain('module-manager', 'settings', 'settings-filament', 'search', 'search-api')
        ->and(array_search('module-manager', $names, true))->toBeLessThan(array_search('settings', $names, true))
        ->and(array_search('settings', $names, true))->toBeLessThan(array_search('settings-filament', $names, true))
        ->and(array_search('search', $names, true))->toBeLessThan(array_search('search-api', $names, true));
});

it('rejects a selected module whose local package dependency is disabled', function () {
    $dependency = Manifest::fromFile(base_path('modules/module-manager/module.json'));
    $dependentPath = sys_get_temp_dir().'/dependent-module.json';
    file_put_contents($dependentPath, json_encode([
        'name' => 'dependent', 'display_name' => 'Dependent', 'description' => 'test',
        'version' => '1.0.0', 'category' => 'foundation', 'provider' => 'Provider',
        'requires' => ['packages' => ['liberusoftware/module-manager' => '^1.0']],
        'capabilities' => ['test.dependent'], 'features' => ['Test dependent'], 'default_enabled' => true,
    ], JSON_THROW_ON_ERROR));
    $dependent = Manifest::fromFile($dependentPath);

    expect(fn () => (new ModuleRegistry(['module-manager' => $dependency, 'dependent' => $dependent]))
        ->resolve([], ['module-manager']))->toThrow(DependencyResolutionFailed::class);
});
