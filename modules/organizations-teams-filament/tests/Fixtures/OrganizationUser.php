<?php

namespace Liberu\Foundation\OrganizationsFilament\Tests\Fixtures;

use Liberu\Foundation\Organizations\Contracts\OrganizationActor;
use Liberu\PackageTestbench\TestUser;

/**
 * `TeamPolicy` type-hints `OrganizationActor`, and the testbench's `TestUser`
 * implements none of the fleet's actor contracts by design — carrying them would
 * drag every contract package into all 44 dev trees. A package needing one
 * subclasses it here.
 *
 * The implementation is the smallest that lets the policy run: ownership by the
 * `user_id` the resource's `owner` relation already uses, and membership defined
 * as ownership, because this suite composes no membership pivot.
 */
final class OrganizationUser extends TestUser implements OrganizationActor
{
    protected $table = 'users';

    /** @param mixed $team */
    public function belongsToTeam($team): bool
    {
        return $this->ownsTeam($team);
    }

    /** @param mixed $team */
    public function ownsTeam($team): bool
    {
        return $team !== null && $this->getKey() === $team->user_id;
    }
}
