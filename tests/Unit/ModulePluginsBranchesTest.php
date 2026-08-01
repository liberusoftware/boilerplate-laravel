<?php

use App\Filament\ModulePlugins;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

class CoverageContainerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'container';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}

class CoverageDuplicatePlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'duplicate';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}

class CoverageSecondDuplicatePlugin extends CoverageDuplicatePlugin {}

class CoverageInvalidPlugin
{
    public static function make(): object
    {
        return new stdClass();
    }
}

function pluginManifest(string $name, string $plugin): Manifest
{
    $dir = sys_get_temp_dir().'/plugin-module-'.bin2hex(random_bytes(5));
    mkdir($dir, 0777, true);
    file_put_contents($dir.'/module.json', json_encode([
        'name' => $name, 'display_name' => $name, 'description' => 'Test', 'version' => '1.0.0',
        'category' => 'presentation', 'provider' => ServiceProvider::class,
        'requires' => ['packages' => [], 'capabilities' => []], 'capabilities' => [],
        'default_enabled' => true, 'presentation' => ['filament' => ['admin' => [$plugin]]],
    ], JSON_THROW_ON_ERROR));
    file_put_contents($dir.'/composer.json', json_encode(['name' => 'local/'.$name]));

    return Manifest::fromFile($dir.'/module.json');
}

it('resolves a plugin through the Laravel container', function () {
    $manifest = pluginManifest('container-plugin', CoverageContainerPlugin::class);
    $plugins = (new ModulePlugins(new ModuleRegistry([$manifest->name() => $manifest])))->forPanel('admin');
    expect($plugins)->toHaveCount(1)
        ->and($plugins[0])->toBeInstanceOf(CoverageContainerPlugin::class);
});

it('rejects invalid plugins and duplicate plugin identifiers', function () {
    $invalid = pluginManifest('invalid-plugin', CoverageInvalidPlugin::class);
    expect(fn () => (new ModulePlugins(new ModuleRegistry([$invalid->name() => $invalid])))->forPanel('admin'))
        ->toThrow(RuntimeException::class, 'invalid Filament plugin');

    $one = pluginManifest('duplicate-one', CoverageDuplicatePlugin::class);
    $two = pluginManifest('duplicate-two', CoverageSecondDuplicatePlugin::class);
    expect(fn () => (new ModulePlugins(new ModuleRegistry([$one->name() => $one, $two->name() => $two])))->forPanel('admin'))
        ->toThrow(RuntimeException::class, 'Duplicate Filament plugin id');
});
