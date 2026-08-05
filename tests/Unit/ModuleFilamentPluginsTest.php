<?php

use App\Filament\ModulePlugins;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleManagerServiceProvider;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

it('composes enabled admin plugins from module manifests', function () {
    $ids = collect(app(ModulePlugins::class)->forPanel('admin'))->map->getId()->all();

    expect($ids)->toContain('filament-shield')
        ->toContain('liberu-module-manager')
        ->toContain('liberu-identity')
        ->toContain('liberu-organizations')
        ->toContain('liberu-settings');
});

it('composes enabled application plugins from module manifests', function () {
    $ids = collect(app(ModulePlugins::class)->forPanel('app'))->map->getId()->all();

    expect($ids)->toContain('liberu-sessions-devices');
});

/**
 * A registry over throwaway manifests on disk.
 *
 * `Manifest` and `ModuleRegistry` are both final and `Manifest` has a private
 * constructor, so these guards can only be reached through real files — which is
 * also the honest test: it is `fromFile` that every real caller goes through.
 *
 * @param  array<string, list<class-string>>  $pluginsByModule
 */
function registryDeclaring(array $pluginsByModule): ModuleRegistry
{
    $root = sys_get_temp_dir().'/module-plugins-'.bin2hex(random_bytes(6));
    $modules = [];

    foreach ($pluginsByModule as $name => $plugins) {
        mkdir($root.'/'.$name, recursive: true);
        file_put_contents($root.'/'.$name.'/module.json', json_encode([
            'name' => $name,
            'display_name' => $name,
            'description' => $name,
            'version' => '1.1.0',
            'category' => 'presentation',
            'provider' => ModuleManagerServiceProvider::class,
            'requires' => [],
            'capabilities' => [],
            'features' => [$name.' panel'],
            'default_enabled' => true,
            'presentation' => ['filament' => ['admin' => $plugins]],
        ], JSON_THROW_ON_ERROR));

        $modules[$name] = Manifest::fromFile($root.'/'.$name.'/module.json');
    }

    return new ModuleRegistry($modules);
}

it('rejects two modules claiming the same plugin id', function () {
    // The manifests cannot express this collision statically — the id lives on the
    // plugin instance, not in module.json — so this guard is the only thing standing
    // between a copy-pasted plugin class and a 500 on whichever panel composes second.
    // It is also why the architecture suite does not assert uniqueness: composing the
    // panels to boot the app would hit this first.
    $registry = registryDeclaring([
        'first' => [FilamentShieldPlugin::class],
        'second' => [FilamentShieldPlugin::class],
    ]);

    expect(fn () => (new ModulePlugins($registry))->forPanel('admin'))
        ->toThrow(RuntimeException::class, 'Duplicate Filament plugin id [filament-shield].');
});

it('rejects a declared class that is not a Filament plugin', function () {
    $registry = registryDeclaring(['broken' => [stdClass::class]]);

    expect(fn () => (new ModulePlugins($registry))->forPanel('admin'))
        ->toThrow(RuntimeException::class, 'Module [broken] returned an invalid Filament plugin.');
});

it('omits plugins when their presentation module is disabled', function () {
    // identity-core-filament is the subject because no other manifest requires it, so
    // disabling it exercises plugin composition rather than dependency resolution.
    config(['modules.disabled' => ['identity-core-filament']]);
    $ids = collect(app(ModulePlugins::class)->forPanel('admin'))->map->getId()->all();

    expect($ids)->not->toContain('liberu-identity');
});
