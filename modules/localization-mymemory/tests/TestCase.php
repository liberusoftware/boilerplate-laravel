<?php

namespace Liberu\Foundation\Localization\MyMemory\Tests;

use Liberu\Localization\Contracts\TranslationProvider;
use Liberu\Localization\Contracts\TranslationProviderRegistry;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app->instance(TranslationProviderRegistry::class, new class() implements TranslationProviderRegistry
        {
            private array $providers = [];

            public function register(TranslationProvider $provider): void
            {
                $this->providers[$provider->name()] = $provider;
            }

            public function get(string $name): TranslationProvider
            {
                return $this->providers[$name];
            }

            public function all(): array
            {
                return $this->providers;
            }
        });
    }

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
