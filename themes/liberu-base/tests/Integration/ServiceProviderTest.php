<?php

namespace Liberu\Themes\Base\Tests\Integration;

use Liberu\Themes\Base\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_declared_service_provider_boots_in_testbench(): void
    {
        $manifest = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/theme.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertNotNull($this->app->getProvider($manifest['provider']));
    }
}
