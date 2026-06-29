<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class AssignDefaultTeam
{
    public function handle(Request $request, Closure $next)
    {
        if (! Filament::getTenant() && auth()->check()) {
            $user = auth()->user();
            $defaultTeam = $user->latestTeam ?? $user->ownedTeams()->first();
            if (! $defaultTeam) {
                $defaultTeam = $user->ownedTeams()->create([
                    'name' => $user->name."'s Team",
                    'personal_team' => true,
                ]);
                $user->current_team_id = $defaultTeam->id;
                $user->save();
            }
            Filament::setTenant($defaultTeam);
        }

        return $next($request);
    }
}
