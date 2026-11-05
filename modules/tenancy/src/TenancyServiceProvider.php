<?php

declare(strict_types=1);

namespace Liberu\Foundation\Tenancy;

use Illuminate\Support\ServiceProvider;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantState::class);
    }
}
