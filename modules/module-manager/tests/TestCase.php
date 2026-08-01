<?php

namespace Liberu\Foundation\ModuleManager\Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('modules.paths', [dirname(__DIR__)]);
        $app['config']->set('modules.enabled', ['module-manager']);
        $app['config']->set('modules.disabled', []);
    }

    protected function getPackageProviders($app): array
    {
        $app['config']->set('modules.paths', [dirname(__DIR__)]);
        $app['config']->set('modules.enabled', ['module-manager']);

        $manifest = json_decode(
            file_get_contents(dirname(__DIR__).'/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return [LivewireServiceProvider::class, $manifest['provider']];
    }
}
