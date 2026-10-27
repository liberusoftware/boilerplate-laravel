<?php

namespace Liberu\Foundation\UsersRoles;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\UsersRoles\Policies\UserPolicy;

final class UsersRolesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $userModel = config('auth.providers.users.model');

        if (is_string($userModel) && ! Gate::getPolicyFor($userModel)) {
            Gate::policy($userModel, UserPolicy::class);
        }
    }
}
