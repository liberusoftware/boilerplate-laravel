<?php

namespace Liberu\Foundation\Organizations\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Foundation\Organizations\Models\Team;
use RuntimeException;

final class TransferOwnership
{
    public function handle(Team $team, int|string $currentOwner, int|string $successor, bool $recentlyAuthenticated): void
    {
        if (! $recentlyAuthenticated || (string) $team->user_id !== (string) $currentOwner) {
            throw new RuntimeException('Recent owner authentication is required.');
        }$eligible = DB::table('team_user')->where('team_id', $team->id)->where('user_id', $successor)->where('status', 'active')->exists();
        if (! $eligible) {
            throw new RuntimeException('The successor must be an active member.');
        }DB::transaction(fn () => $team->forceFill(['user_id' => $successor])->save());
    }
}
