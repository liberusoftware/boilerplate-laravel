<?php

namespace Liberu\Foundation\Authorization;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Authorization\Contracts\PrivilegedActor;
use Liberu\Foundation\Authorization\Models\Role;
use Liberu\Foundation\Authorization\Policies\RolePolicy;
use Liberu\Foundation\Authorization\Registry\PermissionRegistry;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::before(fn ($actor): ?bool => $actor instanceof PrivilegedActor && $actor->isSuperAdmin() ? true : null);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
