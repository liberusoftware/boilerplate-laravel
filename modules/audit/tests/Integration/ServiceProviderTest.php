<?php

namespace Liberu\Foundation\Audit\Tests\Integration;

use Liberu\Foundation\Audit\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_declared_service_provider_boots_in_testbench(): void
    {
        $manifest = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/module.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertNotNull($this->app->getProvider($manifest['provider']));
    }
}
