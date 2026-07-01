<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super admins bypass every policy, regardless of the active team context.
        // Shield's define_via_gate uses team-scoped hasRole(), which returns false
        // when no team is set on the request (Filament nav renders before the tenant
        // team is synced) — leaving the admin panel empty. isSuperAdmin() checks the
        // role across all teams, so the bypass is reliable.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        // Gate the Pulse dashboard to admins (Telescope has its own gate).
        Gate::define('viewPulse', fn (User $user) => $user->isAdmin());
    }
}
