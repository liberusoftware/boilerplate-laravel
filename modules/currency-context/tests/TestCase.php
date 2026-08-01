<?php

namespace Liberu\Foundation\Currency\Tests;

use Liberu\Foundation\Currency\CurrencyServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [CurrencyServiceProvider::class];
    }
}
