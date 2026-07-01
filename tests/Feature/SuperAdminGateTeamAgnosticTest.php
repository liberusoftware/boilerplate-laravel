<?php

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Modules\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// Shield's define_via_gate uses team-scoped hasRole(), which returns false when
// no team context is set during a request — the super_admin then sees an empty
// admin panel. Visibility must not depend on the team context being set.

function makeSuperAdmin(): User
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $user->assignRole('super_admin');
    $user->unsetRelation('roles');

    return $user;
}

it('detects super_admin regardless of the active team context', function () {
    $user = makeSuperAdmin();

    app(PermissionRegistrar::class)->setPermissionsTeamId(null); // simulate a request with no team context

    expect($user->isSuperAdmin())->toBeTrue();
});

it('lets a super_admin view resources with NO team context set', function () {
    $user = makeSuperAdmin();

    app(PermissionRegistrar::class)->setPermissionsTeamId(null); // the failing production case

    expect($user->can('viewAny', User::class))->toBeTrue();
    expect($user->can('viewAny', Post::class))->toBeTrue();
});
