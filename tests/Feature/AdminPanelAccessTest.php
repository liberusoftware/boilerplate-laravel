<?php

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;

it('lets any authenticated user reach the app panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('app')))->toBeTrue();
});

it('denies the admin panel to a user without an admin role', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('allows the admin panel to a super_admin', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $user->assignRole('super_admin');

    expect($user->hasAdminAccess())->toBeTrue();
    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});
