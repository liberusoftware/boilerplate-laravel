<?php

namespace Liberu\Foundation\Search\Tests\Fixtures;

use Liberu\Foundation\Search\Concerns\Searchable;
use Liberu\PackageTestbench\TestUser;

/**
 * What an application's own user model is expected to look like: the testbench
 * actor plus the trait this package ships. Before that trait existed the scope
 * lived only in the host, so this fixture could not have been written without
 * copying host code — which is exactly the gap it now proves is closed.
 */
final class SearchableUser extends TestUser
{
    use Searchable;

    protected $table = 'users';
}
