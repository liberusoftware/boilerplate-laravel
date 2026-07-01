<?php

use App\Models\Module;
use App\Modules\Events\ModuleDisabled;
use App\Modules\Events\ModuleEnabled;
use App\Modules\Events\ModuleInstalled;
use App\Modules\Events\ModuleUninstalled;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\Event;

it('discovers the Blog module fixture from disk', function () {
    $manager = new ModuleManager();

    expect($manager->has('Blog'))->toBeTrue()
        ->and($manager->get('Blog'))->not->toBeNull()
        ->and($manager->getAllModulesInfo())->not->toBeEmpty();
});

it('does not treat framework subfolders (Contracts/Events/Traits) as modules', function () {
    $keys = (new ModuleManager())->all()->keys()->all();

    expect($keys)->toBe(['Blog']);
});

it('persists enable then disable state to the modules table', function () {
    (new ModuleManager())->enable('Blog');
    expect(Module::findByName('Blog'))->not->toBeNull()
        ->and(Module::findByName('Blog')->enabled)->toBeTrue();

    (new ModuleManager())->disable('Blog');
    expect(Module::findByName('Blog')->enabled)->toBeFalse();
});

it('dispatches lifecycle events on enable and disable', function () {
    Event::fake([ModuleEnabled::class, ModuleDisabled::class]);

    $manager = new ModuleManager();
    $manager->enable('Blog');
    $manager->disable('Blog');

    Event::assertDispatched(ModuleEnabled::class);
    Event::assertDispatched(ModuleDisabled::class);
});

it('dispatches install and uninstall lifecycle events', function () {
    Event::fake([ModuleInstalled::class, ModuleUninstalled::class]);

    $manager = new ModuleManager();
    $manager->install('Blog');
    $manager->uninstall('Blog');

    Event::assertDispatched(ModuleInstalled::class);
    Event::assertDispatched(ModuleUninstalled::class);
});
