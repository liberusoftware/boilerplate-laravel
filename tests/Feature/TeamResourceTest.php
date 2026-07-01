<?php

use App\Filament\Resources\TeamResource;
use App\Filament\Resources\TeamResource\Pages\ListTeams;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('is not scoped to the team tenant (Team is the tenant itself)', function () {
    expect(TeamResource::isScopedToTenant())->toBeFalse();
});

it('renders the teams list for a super admin', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $admin->id]);
    $admin->forceFill(['current_team_id' => $team->id])->save();
    $otherTeam = Team::factory()->create();

    Gate::before(fn () => true);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    Livewire::test(ListTeams::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$team, $otherTeam]);
});
