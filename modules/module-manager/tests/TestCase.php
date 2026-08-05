<?php

namespace Liberu\Foundation\ModuleManager\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $this->usePackageOrCompositionBasePath($app);
        $app['config']->set('modules.paths', [dirname(__DIR__)]);
        $app['config']->set('modules.enabled', ['module-manager']);
        $app['config']->set('modules.disabled', []);
    }

    protected function getPackageProviders($app): array
    {
        $this->usePackageOrCompositionBasePath($app);
        $app['config']->set('modules.paths', [dirname(__DIR__)]);
        $app['config']->set('modules.enabled', ['module-manager']);

        $manifest = json_decode(
            file_get_contents(dirname(__DIR__).'/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return [$manifest['provider']];
    }

    private function usePackageOrCompositionBasePath($app): void
    {
        // Inside the monorepo, point the app at the composition root so discovery
        // sees every sibling package. Standalone, leave Testbench's own skeleton
        // base path alone — the package root has no bootstrap/cache to write to.
        $compositionRoot = dirname(__DIR__, 3);

        if (is_dir($compositionRoot.'/themes')) {
            $app->setBasePath($compositionRoot);
        }
    }
}
