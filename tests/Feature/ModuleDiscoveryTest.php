<?php

use App\Models\Module;
use App\Modules\Events\ModuleDisabled;
use App\Modules\Events\ModuleEnabled;
use App\Modules\Events\ModuleInstalled;
use App\Modules\Events\ModuleUninstalled;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\Event;

it('discovers the BlogModule fixture from disk', function () {
    $manager = new ModuleManager();

    expect($manager->has('BlogModule'))->toBeTrue()
        ->and($manager->get('BlogModule'))->not->toBeNull()
        ->and($manager->getAllModulesInfo())->not->toBeEmpty();
});

it('does not treat framework subfolders (Contracts/Events/Traits) as modules', function () {
    $keys = (new ModuleManager())->all()->keys()->all();

    expect($keys)->toBe(['BlogModule']);
});

it('persists enable then disable state to the modules table', function () {
    (new ModuleManager())->enable('BlogModule');
    expect(Module::findByName('BlogModule'))->not->toBeNull()
        ->and(Module::findByName('BlogModule')->enabled)->toBeTrue();

    (new ModuleManager())->disable('BlogModule');
    expect(Module::findByName('BlogModule')->enabled)->toBeFalse();
});

it('dispatches lifecycle events on enable and disable', function () {
    Event::fake([ModuleEnabled::class, ModuleDisabled::class]);

    $manager = new ModuleManager();
    $manager->enable('BlogModule');
    $manager->disable('BlogModule');

    Event::assertDispatched(ModuleEnabled::class);
    Event::assertDispatched(ModuleDisabled::class);
});

it('dispatches install and uninstall lifecycle events', function () {
    Event::fake([ModuleInstalled::class, ModuleUninstalled::class]);

    $manager = new ModuleManager();
    $manager->install('BlogModule');
    $manager->uninstall('BlogModule');

    Event::assertDispatched(ModuleInstalled::class);
    Event::assertDispatched(ModuleUninstalled::class);
});
