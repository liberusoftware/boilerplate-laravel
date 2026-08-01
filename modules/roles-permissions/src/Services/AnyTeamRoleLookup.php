<?php

namespace Liberu\Foundation\Authorization\Services;

use Illuminate\Support\Facades\DB;
use Liberu\Foundation\Authorization\Contracts\PrivilegedActor;

final class AnyTeamRoleLookup
{
    /** @param list<string> $roles */
    public function holds(PrivilegedActor $actor, array $roles): bool
    {
        if ($roles === []) {
            return false;
        }

        $pivot = (string) config('permission.table_names.model_has_roles', 'model_has_roles');
        $roleTable = (string) config('permission.table_names.roles', 'roles');

        return DB::table($pivot)
            ->join($roleTable, "{$roleTable}.id", '=', "{$pivot}.role_id")
            ->where("{$pivot}.model_id", $actor->authorizationIdentifier())
            ->where("{$pivot}.model_type", $actor->authorizationType())
            ->whereIn("{$roleTable}.name", $roles)
            ->exists();
    }
}
