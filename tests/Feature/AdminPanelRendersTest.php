<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\RolesPermissions\Models\Role;

uses(RefreshDatabase::class);

it('renders the full admin panel for a super_admin without the Role team() error', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $admin->id]);
    $admin->forceFill(['current_team_id' => $team->id])->save();
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $admin->assignRole('super_admin');

    // Full HTTP render (navigation + badges), not a single Livewire component.
    $this->actingAs($admin)
        ->get(Filament::getPanel('admin')->getUrl($team))
        ->assertSuccessful();
});
