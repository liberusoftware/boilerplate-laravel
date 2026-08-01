<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Liberu\Foundation\Authorization\Services\AnyTeamRoleLookup;
use Liberu\Foundation\Organizations\Models\Team;
use Spatie\Permission\Models\Role;

it('treats an allowlisted email as an admin', function () {
    config(['app.admin_emails' => ['boss@example.com']]);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    $other = User::factory()->create(['email' => 'nobody@example.com']);

    expect($admin->isAdmin())->toBeTrue();
    expect($other->isAdmin())->toBeFalse()
        ->and(app(AnyTeamRoleLookup::class))->toBe(app(AnyTeamRoleLookup::class))
        ->and(app(AnyTeamRoleLookup::class)->hasRoleInAnyTeam($other, []))->toBeFalse();
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

it('finds an administrator role without an active team context', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);

    setPermissionsTeamId($team->id);
    $user->assignRole(Role::create(['name' => 'admin']));
    setPermissionsTeamId(null);

    expect($user->fresh()->hasAdminAccess())->toBeTrue();
});

it('uses the configured super administrator role consistently', function () {
    config()->set('filament-shield.super_admin.name', 'platform_owner');
    $owner = User::factory()->create();
    $legacy = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);

    setPermissionsTeamId($team->id);
    $owner->assignRole(Role::create(['name' => 'platform_owner']));
    $legacy->assignRole(Role::create(['name' => 'super_admin']));
    setPermissionsTeamId(null);

    expect($owner->fresh()->isSuperAdmin())->toBeTrue()
        ->and($owner->fresh()->isAdmin())->toBeTrue()
        ->and($owner->fresh()->hasAdminAccess())->toBeTrue()
        ->and($legacy->fresh()->isSuperAdmin())->toBeFalse()
        ->and($legacy->fresh()->isAdmin())->toBeFalse();
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
