<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

it('treats an allowlisted email as an admin', function () {
    config(['app.admin_emails' => ['boss@example.com']]);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    $other = User::factory()->create(['email' => 'nobody@example.com']);

    expect($admin->isAdmin())->toBeTrue();
    expect($other->isAdmin())->toBeFalse();
});

it('treats a super_admin (in any team) as an admin regardless of team context', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);

    setPermissionsTeamId($team->id);
    $role = Role::create(['name' => 'super_admin']);
    $user->assignRole($role);

    // Leave the team context — the gate runs on plain web requests with no active team.
    setPermissionsTeamId(null);

    expect($user->fresh()->isAdmin())->toBeTrue();
});

it('gates Telescope and Pulse to admins only', function () {
    config(['app.admin_emails' => ['boss@example.com']]);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    $plain = User::factory()->create(['email' => 'plain@example.com']);

    expect(Gate::forUser($admin)->allows('viewTelescope'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('viewPulse'))->toBeTrue();
    expect(Gate::forUser($plain)->allows('viewTelescope'))->toBeFalse();
    expect(Gate::forUser($plain)->allows('viewPulse'))->toBeFalse();
});
