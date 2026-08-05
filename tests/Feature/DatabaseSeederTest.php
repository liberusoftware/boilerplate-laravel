<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\RolesPermissions\Models\Role;

use function Pest\Laravel\seed;

it('seeds a default team and an admin user with the super_admin role', function () {
    seed(DatabaseSeeder::class);

    // Default team
    $team = Team::where('name', 'Default')->first();
    expect($team)->not->toBeNull();

    // Admin user on the team
    $admin = User::where('email', 'admin@example.com')->first();
    expect($admin)->not->toBeNull();
    expect($admin->teams()->where('teams.id', $team->id)->exists())->toBeTrue();

    // super_admin role assigned in the team's permission context
    setPermissionsTeamId($team->id);
    expect(Role::where('name', 'super_admin')->exists())->toBeTrue();
    expect($admin->fresh()->hasRole('super_admin'))->toBeTrue();
});
