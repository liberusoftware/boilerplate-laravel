<?php

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

// A fresh install generates no Shield permissions, so a super_admin only sees
// resources if it BYPASSES policies via Shield's gate (define_via_gate). Without
// that, the admin panel is empty. This locks the gate on.

it('defines the super admin via gate so it bypasses policies', function () {
    expect(config('filament-shield.super_admin.define_via_gate'))->toBeTrue();
});

it('lets a super_admin view resources even with no permissions generated', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $user->assignRole('super_admin');
    $user->unsetRelation('roles');

    // No permissions exist (shield:generate not run); visibility must come from the gate.
    expect(Permission::count())->toBe(0);
    expect($user->can('viewAny', User::class))->toBeTrue();
});
