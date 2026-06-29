<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->admin = User::factory()->create(['current_team_id' => $this->team->id]);

    // Bypass Shield/policy gates — this test covers the form schema, not authorization.
    Gate::before(fn () => true);

    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($this->team);
});

it('saves account settings: email verified at and current team', function () {
    $target = User::factory()->create(['email_verified_at' => null]);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm([
            'email_verified_at' => now()->startOfMinute(),
            'current_team_id' => $this->team->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $target->refresh();

    expect($target->email_verified_at)->not->toBeNull()
        ->and($target->current_team_id)->toBe($this->team->id);
});
