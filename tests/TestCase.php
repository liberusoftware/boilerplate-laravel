<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        // Reset Fortify/Jetstream static route registration flags that persist
        // across test instances via static properties (set to false by Filament panel providers)
        Fortify::$registersRoutes = true;
        if (class_exists(Jetstream::class)) {
            Jetstream::$registersRoutes = true;
        }

        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
