<?php

use App\Filament\ModulePlugins;

it('composes enabled admin plugins from module manifests', function () {
    $ids = collect(app(ModulePlugins::class)->forPanel('admin'))->map->getId()->all();

    expect($ids)->toContain('filament-shield')
        ->toContain('liberu-foundation-admin')
        ->toContain('liberu-identity')
        ->toContain('liberu-organizations')
        ->toContain('liberu-settings');
});

it('composes enabled application plugins from module manifests', function () {
    $ids = collect(app(ModulePlugins::class)->forPanel('app'))->map->getId()->all();

    expect($ids)->toContain('liberu-foundation-account');
});

it('omits plugins when their presentation module is disabled', function () {
    // identity-filament is the subject because no other manifest requires it, so
    // disabling it exercises plugin composition rather than dependency resolution.
    config(['modules.disabled' => ['identity-filament']]);
    $ids = collect(app(ModulePlugins::class)->forPanel('admin'))->map->getId()->all();

    expect($ids)->not->toContain('liberu-identity');
});
