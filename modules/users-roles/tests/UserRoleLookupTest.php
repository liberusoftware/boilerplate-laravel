<?php

use Liberu\Foundation\UsersRoles\Models\User;
use Liberu\Foundation\UsersRoles\Services\UserRoleLookup;

it('rejects an unsaved user without requiring a database connection', function () {
    expect((new UserRoleLookup())->hasRoleInAnyTeam(new User(), 'super_admin'))->toBeFalse();
});

it('retains the concrete user identity for role assignments', function () {
    $user = (new User())->forceFill(['id' => 42]);

    expect($user->authorizationIdentifier())->toBe(42)
        ->and($user->authorizationType())->toBe(User::class)
        ->and((new UserRoleLookup())->hasRoleInAnyTeam($user, []))->toBeFalse();
});
