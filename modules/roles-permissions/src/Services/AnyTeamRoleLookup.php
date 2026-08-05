<?php

namespace Liberu\Foundation\RolesPermissions\Services;

use Illuminate\Support\Facades\DB;
use Liberu\Foundation\RolesPermissions\Contracts\PrivilegedActor;

final class AnyTeamRoleLookup
{
    /** @param string|list<string> $roles */
    public function hasRoleInAnyTeam(PrivilegedActor $actor, string|array $roles): bool
    {
        $roles = (array) $roles;
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
