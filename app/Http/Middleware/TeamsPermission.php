<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamsPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to access this area.');
        }

        // Allow staff and admin users to access without team restrictions
        if ($user->hasRole(['staff', 'admin'])) {
            return $next($request);
        }

        if (!$user->currentTeam) {
            // Redirect to a default route or show an error
            return redirect()->route('home')->with('error', 'You must be part of a team to access this area.');
        }

        // Check if the requested team matches the user's current team
        $requestedTeamId = $request->route('tenant');
        if ($requestedTeamId && $requestedTeamId != $user->currentTeam->id) {
            return redirect()->route('staff.dashboard', ['tenant' => $user->currentTeam->id])
                ->with('error', 'You do not have permission to access this team.');
        }

        // Check if the user has permission to access the current route
        // You can implement your team-based permission logic here

        return $next($request);
    }
}
