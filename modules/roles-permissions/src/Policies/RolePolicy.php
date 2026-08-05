<?php

declare(strict_types=1);

namespace Liberu\Foundation\RolesPermissions\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('roles-permissions.roles.view-any');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('roles-permissions.roles.view');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('roles-permissions.roles.create');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('roles-permissions.roles.update');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('roles-permissions.roles.delete');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('roles-permissions.roles.delete-any');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('roles-permissions.roles.restore');
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('roles-permissions.roles.force-delete');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('roles-permissions.roles.force-delete-any');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('roles-permissions.roles.restore-any');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('roles-permissions.roles.replicate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('roles-permissions.roles.reorder');
    }
}
