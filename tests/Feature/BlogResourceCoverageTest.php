<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Liberu\Blog\Filament\Resources\PostResource\Pages\ListPosts;
use Liberu\Foundation\Organizations\Models\Team;
use Livewire\Livewire;

it('renders the post resource table', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create(['current_team_id' => $team->id]);
    Gate::before(fn () => true);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    Livewire::test(ListPosts::class)->assertOk();
});
