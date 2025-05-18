<?php

namespace App\Http\Controllers;

use App\Mail\TeamInvitation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamInvitationController extends Controller
{
    public function sendInvitation(Request $request)
    {
        $request->validate([
            'email'   => 'required|email',
            'team_id' => 'required|exists:teams,id',
        ]);

        $user = User::firstOrCreate(['email' => $request->email], ['password' => bcrypt(Str::random(10))]);
        $team = Team::findOrFail($request->team_id);

        $invitationToken = Str::random(32);
        $user->invitations()->create([
            'team_id' => $team->id,
            'token'   => $invitationToken,
        ]);

        Mail::to($request->email)->send(new TeamInvitation($user, $team, $invitationToken));

        return response()->json(['message' => 'Invitation sent successfully.']);
    }

    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:invitations,token',
        ]);

        $invitation = Invitation::where('token', $request->token)->firstOrFail();
        $team = Team::findOrFail($invitation->team_id);
        $team->members()->attach($invitation->user_id);

        $invitation->update(['accepted' => true]);

        return response()->json(['message' => 'Invitation accepted successfully.']);
    }
}
