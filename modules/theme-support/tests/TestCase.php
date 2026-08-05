<?php

namespace Liberu\Foundation\Theme\Tests;

use Illuminate\Hashing\HashServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        $themePath = $app->basePath('themes/default');
        if (! is_dir($themePath)) {
            mkdir($themePath, 0777, true);
        }

        file_put_contents($themePath.'/composer.json', json_encode([
            'name' => 'liberusoftware/theme-default',
            'type' => 'liberu-theme',
            'extra' => ['liberu' => ['name' => 'default']],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($themePath.'/theme.json', json_encode([
            'name' => 'default',
            'display_name' => 'Default',
            'version' => '1.0.0',
            'provider' => HashServiceProvider::class,
            'type' => 'shared',
            'parent' => '',
            'optimized_for' => [],
            'tested_with' => [],
            'required_capabilities' => [],
            'optional_capabilities' => [],
            'supports' => [],
            'assets' => ['css' => [], 'js' => []],
        ], JSON_THROW_ON_ERROR));

        $manifest = json_decode(
            file_get_contents(dirname(__DIR__).'/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return [$manifest['provider']];
    }
}
