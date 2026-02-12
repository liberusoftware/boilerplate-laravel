<?php

namespace App\Http\Middleware;

use BezhanSalleh\FilamentShield\Support\Utils;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamsPermission
{
    public function handle(Request $request, Closure $next)
    {
        if (Utils::isTenancyEnabled() && ($team = Filament::getTenant())) {
            setPermissionsTeamId($team->id);
        }
        return $next($request);
    }
}
