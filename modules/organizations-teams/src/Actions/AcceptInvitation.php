<?php

namespace Liberu\Foundation\Organizations\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AcceptInvitation
{
    public function handle(string $token, Authenticatable $actor, string $email): void
    {
        DB::transaction(function () use ($token, $actor, $email): void {
            $invitation = DB::table('team_invitations')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $invitation || $invitation->revoked_at || $invitation->accepted_at || ! $invitation->expires_at || now()->greaterThan($invitation->expires_at) || ! hash_equals(mb_strtolower($invitation->email), mb_strtolower(trim($email)))) {
                throw new RuntimeException('Invitation is invalid or expired.');
            }DB::table('team_user')->updateOrInsert(['team_id' => $invitation->team_id, 'user_id' => $actor->getAuthIdentifier()], ['role' => $invitation->role, 'status' => 'active', 'invited_by' => $invitation->invited_by, 'effective_from' => now(), 'terms_accepted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('team_invitations')->where('id', $invitation->id)->update(['accepted_at' => now(), 'updated_at' => now()]);
        });
    }
}
