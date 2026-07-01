<?php

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedTeamUser(bool $superAdmin): User
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    if ($superAdmin) {
        setPermissionsTeamId($team->id);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
        $user->assignRole('super_admin');
    }

    return $user;
}

it('sends a super_admin to the admin panel after login', function () {
    $admin = seedTeamUser(superAdmin: true);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertRedirectContains('/admin');
});

it('sends a normal user to the app panel after login', function () {
    $user = seedTeamUser(superAdmin: false);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('filament.app.pages.dashboard'));
});
