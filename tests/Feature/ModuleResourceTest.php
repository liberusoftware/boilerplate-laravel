<?php

use App\Filament\Resources\ModuleResource\Pages\ListModules;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the modules list (non-Eloquent data source)', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);

    Gate::before(fn () => true);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    // Must actually list the discovered module — a bare assertOk() passed while the
    // table was silently empty (the loader never found BlogModule).
    Livewire::test(ListModules::class)
        ->assertOk()
        ->assertSee('BlogModule');
});
