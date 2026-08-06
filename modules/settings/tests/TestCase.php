<?php

namespace Liberu\Foundation\Settings\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\PackageTestbench\PackageTestCase;

/**
 * The package's own settings migrations, which it publishes rather than loads.
 *
 * In an application they are published to `database/settings` and Spatie's
 * migrator finds them there. Nothing publishes them under Testbench, so the
 * suite points the migrator at the package's copies — the same files the host
 * would have received.
 */
abstract class TestCase extends PackageTestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('settings.migrations_paths', [dirname(__DIR__).'/database/settings']);
    }
}
