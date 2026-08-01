<?php

namespace Liberu\Foundation\Organizations\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Liberu\Foundation\Organizations\Models\Team;

final class CurrentTeamResolver
{
    public function resolve(Authenticatable $actor, int|string|null $requested): ?Team
    {
        if ($requested === null) {
            return null;
        }$active = DB::table('team_user')->where('team_id', $requested)->where('user_id', $actor->getAuthIdentifier())->where('status', 'active')->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>', now()))->exists();
        $owned = Team::query()->whereKey($requested)->where('user_id', $actor->getAuthIdentifier())->where('status', 'active')->exists();

        return $active || $owned ? Team::query()->whereKey($requested)->where('status', 'active')->first() : null;
    }
}
