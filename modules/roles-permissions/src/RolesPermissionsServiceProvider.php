<?php

namespace Liberu\Foundation\RolesPermissions;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\RolesPermissions\Contracts\PrivilegedActor;
use Liberu\Foundation\RolesPermissions\Models\Role;
use Liberu\Foundation\RolesPermissions\Policies\RolePolicy;
use Liberu\Foundation\RolesPermissions\Registry\PermissionRegistry;
use Liberu\Foundation\RolesPermissions\Services\AnyTeamRoleLookup;

final class RolesPermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(AnyTeamRoleLookup::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::before(fn ($actor): ?bool => $actor instanceof PrivilegedActor && $actor->isSuperAdmin() ? true : null);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
