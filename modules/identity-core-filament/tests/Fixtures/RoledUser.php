<?php

namespace Liberu\Foundation\IdentityFilament\Tests\Fixtures;

use Liberu\PackageTestbench\TestUser;
use Spatie\Permission\Traits\HasRoles;

/**
 * `UserResource`'s table has a `roles.name` column, so the configured user model
 * must have that relation. The testbench's `TestUser` implements none of the
 * fleet's contracts by design — a package needing one subclasses it here rather
 * than dragging the dependency into every other package's dev tree.
 */
final class RoledUser extends TestUser
{
    use HasRoles;

    protected $table = 'users';
}
