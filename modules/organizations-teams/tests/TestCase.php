<?php

namespace Liberu\Foundation\Organizations\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        $manifest = json_decode(
            file_get_contents(dirname(__DIR__).'/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return [$manifest['provider']];
    }
}
