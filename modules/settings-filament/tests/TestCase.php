<?php

namespace Liberu\Foundation\SettingsFilament\Tests;

use Filament\Facades\Filament;
use Liberu\Foundation\Settings\SettingsServiceProvider;
use Liberu\Foundation\SettingsFilament\Tests\Fixtures\TestPanelProvider;
use Liberu\Foundation\Theme\Providers\ThemeServiceProvider;
use Liberu\PackageTestbench\PackageTestCase;
use Liberu\PackageTestbench\TestUser;
use Liberu\PackageTestbench\UsesTestUser;

/**
 * Filament is the one dependency `PackageTestCase`'s scoped discovery cannot
 * cover on its own.
 *
 * It registers `extra.laravel.providers` of this package's *direct*
 * dependencies, which for `filament/filament` is exactly one provider. A panel
 * needs the rest of the stack — support, schemas, forms, tables, actions,
 * notifications, widgets, Livewire, the icon packages — and every one of those
 * is transitive. So this widens the same walk to everything installed, which is
 * what Laravel's own discovery does in an application, and appends the fixture
 * panel.
 */
abstract class TestCase extends PackageTestCase
{
    use UsesTestUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Nothing has resolved a panel from a request, and a resource page needs
        // one to be current before it can mount.
        Filament::setCurrentPanel('admin');
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', TestUser::class);

        // The page's theme select resolves ThemeManager, which refuses to boot
        // unless the configured fallback theme is installed. That is why this
        // package dev-requires two themes: the shared root and the one the
        // shipped config names as the fallback.
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return array_values(array_unique([
            ...$this->discoveredProviders(),
            // `require` siblings, so their manifests declare no providers and
            // nothing boots them — installation never implies boot. Named here
            // because the page has no settings store without the settings
            // migration, and its theme select calls ThemeManager. Declaring them
            // in require-dev as well would earn a duplication warning from
            // `composer validate` for saying nothing true.
            SettingsServiceProvider::class,
            ThemeServiceProvider::class,
            ...parent::getPackageProviders($app),
            TestPanelProvider::class,
        ]));
    }

    /**
     * Every `extra.laravel.providers` entry in the installed tree.
     *
     * Sibling Liberu modules are unaffected: their manifests declare that array
     * empty precisely so installation never implies boot, so this picks up the
     * framework packages and nothing else.
     *
     * @return array<int, class-string>
     */
    private function discoveredProviders(): array
    {
        $installed = json_decode(
            (string) file_get_contents($this->packageRoot().'/vendor/composer/installed.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $providers = [];

        foreach ($installed['packages'] ?? [] as $package) {
            foreach ((array) ($package['extra']['laravel']['providers'] ?? []) as $provider) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }
}
