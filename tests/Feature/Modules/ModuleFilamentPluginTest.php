<?php

use App\Filament\Plugins\ModuleFilamentPlugin;

it('exposes a per-segment id and fluent for()', function () {
    $plugin = ModuleFilamentPlugin::make()->for('Admin');

    expect($plugin)->toBeInstanceOf(ModuleFilamentPlugin::class);
    expect($plugin->getId())->toBe('modules-admin');
});

it('is registered on both panels with the right segment', function () {
    // The admin panel registers the Admin segment; the app panel the App segment.
    expect(\Filament\Facades\Filament::getPanel('admin')->getPlugin('modules-admin'))->not->toBeNull();
    expect(\Filament\Facades\Filament::getPanel('app')->getPlugin('modules-app'))->not->toBeNull();
});
