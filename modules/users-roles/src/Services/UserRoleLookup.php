<?php

namespace Liberu\Foundation\UsersRoles\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class UserRoleLookup
{
    /** @param string|list<string> $roles */
    public function hasRoleInAnyTeam(Model $user, string|array $roles): bool
    {
        if ($user->getKey() === null || $roles === []) {
            return false;
        }

        $pivot = (string) config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolesTable = (string) config('permission.table_names.roles', 'roles');
        $modelKey = (string) (config('permission.column_names.model_morph_key') ?: 'model_id');
        $roleKey = (string) (config('permission.column_names.role_pivot_key') ?: 'role_id');

        return DB::connection($user->getConnectionName())->table($pivot)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivot}.{$roleKey}")
            ->where("{$pivot}.{$modelKey}", $user->getKey())
            ->where("{$pivot}.model_type", $user->getMorphClass())
            ->whereIn("{$rolesTable}.name", (array) $roles)
            ->exists();
    }
}
