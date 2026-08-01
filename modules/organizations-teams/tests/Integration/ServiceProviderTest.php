<?php

namespace Modules\organizationsteams\tests\Integration;

use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_declared_service_provider_registers_with_the_application(): void
    {
        $modulePath = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($modulePath.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
        $provider = $manifest['provider'];

        $instance = $this->app->register($provider, true);

        self::assertInstanceOf(ServiceProvider::class, $instance);
        self::assertSame($instance, $this->app->getProvider($provider));
    }
}
