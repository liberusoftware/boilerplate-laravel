<?php

namespace Liberu\Foundation\Search\Tests;

use Liberu\Foundation\Search\Tests\Fixtures\SearchableUser;
use Liberu\PackageTestbench\PackageTestCase;
use Liberu\PackageTestbench\TestUser;
use Liberu\PackageTestbench\UsesTestUser;

/**
 * The user model this package searches is a configured class, never a literal
 * one — an application supplies its own, and the provider defaults the config to
 * `auth.providers.users.model`. The suite supplies the testbench's actor, which
 * is the whole reason `searchUsers()` can be tested outside a composition.
 */
abstract class TestCase extends PackageTestCase
{
    use UsesTestUser;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('search.models.user', SearchableUser::class);
        $app['config']->set('auth.providers.users.model', TestUser::class);
    }
}
