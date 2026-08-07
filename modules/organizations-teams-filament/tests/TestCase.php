<?php

namespace Liberu\Foundation\OrganizationsFilament\Tests;

use Filament\Facades\Filament;
use Laravel\Jetstream\Jetstream;
use Liberu\Foundation\Organizations\OrganizationsServiceProvider;
use Liberu\Foundation\OrganizationsFilament\Tests\Fixtures\OrganizationUser;
use Liberu\Foundation\OrganizationsFilament\Tests\Fixtures\TestPanelProvider;
use Liberu\PackageTestbench\PackageTestCase;
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

        // Team extends Jetstream's, whose owner() resolves Jetstream::userModel().
        // That is a static property defaulting to App\Models\User — a class no
        // package may depend on — so config alone does not redirect it.
        Jetstream::useUserModel(OrganizationUser::class);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // The table's owner.name column resolves Team::owner(), which points at
        // whatever model this names — and TeamPolicy type-hints OrganizationActor,
        // which the plain testbench actor deliberately does not implement.
        $app['config']->set('auth.providers.users.model', OrganizationUser::class);

        // Team is activity-logged. That belongs to the audit module and its
        // migration, neither of which this package depends on; leaving it on
        // would make a resource test fail on a missing activity_log table.
        $app['config']->set('activitylog.enabled', false);

    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return array_values(array_unique([
            ...$this->discoveredProviders(),
            // A `require` sibling, so its manifest declares no providers and nothing
            // boots it — installation never implies boot. Named here because this
            // resource has no table to query without the teams migration it loads.
            // Declaring it in require-dev as well would earn a duplication warning
            // from `composer validate` for saying nothing true.
            OrganizationsServiceProvider::class,
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
