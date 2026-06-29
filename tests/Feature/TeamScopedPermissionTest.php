<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('enables team-scoped permissions in config', function () {
    expect(config('permission.teams'))->toBeTrue();
});

it('binds the app Role and Permission models', function () {
    expect(config('permission.models.role'))->toBe(Role::class)
        ->and(config('permission.models.permission'))->toBe(Permission::class)
        ->and(new Role())->toBeInstanceOf(Spatie\Permission\Models\Role::class)
        ->and(new Permission())->toBeInstanceOf(Spatie\Permission\Models\Permission::class);
});

it('adds team_id to the roles and pivot tables', function () {
    expect(Schema::hasColumn('roles', 'team_id'))->toBeTrue()
        ->and(Schema::hasColumn('model_has_roles', 'team_id'))->toBeTrue()
        ->and(Schema::hasColumn('model_has_permissions', 'team_id'))->toBeTrue();
});

it('scopes a role to its team', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create(['user_id' => $user->id]);
    $teamB = Team::factory()->create(['user_id' => $user->id]);

    setPermissionsTeamId($teamA->id);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $user->assignRole($role);

    expect($user->hasRole('editor'))->toBeTrue();

    setPermissionsTeamId($teamB->id);

    expect($user->fresh()->hasRole('editor'))->toBeFalse();
});
