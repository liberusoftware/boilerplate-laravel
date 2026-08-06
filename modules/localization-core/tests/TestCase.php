<?php

namespace Liberu\Foundation\Localization\Tests;

use Liberu\PackageTestbench\PackageTestCase;
use Liberu\PackageTestbench\UsesTestUser;

/**
 * A composition that has installed this package declares which locales it
 * accepts; the package itself ships no list, and resolves against
 * `localization.locales` falling back to `app.supported_locales`. The suite
 * supplies one, because the resolver's whole job is choosing between them.
 *
 * `users.locale` is `profiles`' column, not this package's — the resolver only
 * reads it off the authenticated actor. That is why `profiles` is a development
 * dependency: the column has to exist for the preference to be readable at all.
 */
abstract class TestCase extends PackageTestCase
{
    use UsesTestUser;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // `localization.locales` and not `app.supported_locales`: the package's own
        // config file reads the application's list once, while config is loading, so
        // setting the application key afterwards would arrive too late.
        $app['config']->set('localization.locales', ['en' => 'English', 'es' => 'Español', 'fr' => 'Français', 'de' => 'Deutsch']);
    }
}
