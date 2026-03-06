<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): \Illuminate\Foundation\Application
    {
        // Reset Fortify/Jetstream static route registration flags that persist
        // across test instances via static properties (set to false by Filament panel providers)
        \Laravel\Fortify\Fortify::$registersRoutes = true;
        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            \Laravel\Jetstream\Jetstream::$registersRoutes = true;
        }

        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }
}
