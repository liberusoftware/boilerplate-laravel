<?php

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('is not scoped to the team tenant (User has no team() relation)', function () {
    expect(UserResource::isScopedToTenant())->toBeFalse();
});

it('renders the users list for a super admin', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create(['current_team_id' => $team->id]);
    $other = User::factory()->create();

    Gate::before(fn () => true);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$admin, $other]);
});
