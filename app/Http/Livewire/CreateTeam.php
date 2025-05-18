<?php

namespace App\Http\Livewire;

use App\Actions\Jetstream\CreateTeam;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Http\Livewire\CreateTeamForm;

class CreateTeam extends CreateTeamForm
{
    /**
     * Create a new team.
     *
     * @return void
     */
    public function createTeam()
    {
        $this->validate();

        $team = app(CreateTeam::class)->create(
            Auth::user(),
            ['name' => $this->state['name']]
        );

        return redirect()->route('filament.pages.edit-team', ['team' => $team]);
    }
}
