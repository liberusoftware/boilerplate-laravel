<?php

namespace Liberu\Foundation\LocalizationLivewire\Tests;

use Liberu\PackageTestbench\PackageTestCase;
use Liberu\PackageTestbench\UsesTestUser;

/**
 * The switcher offers whatever locales the application declares, so the suite
 * declares some. Unlike `localization-core`, this package reads
 * `app.supported_locales` directly and at mount time, so setting it here is
 * early enough.
 *
 * `users.locale` is `profiles`' column — the switcher only writes the choice to
 * the authenticated actor — which is why `profiles` is a development dependency.
 */
abstract class TestCase extends PackageTestCase
{
    use UsesTestUser;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.supported_locales', ['en' => 'English', 'es' => 'Español', 'fr' => 'Français', 'de' => 'Deutsch']);
    }
}
