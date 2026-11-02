<?php

namespace Liberu\Foundation\Tenancy\Jobs\Concerns;

use Liberu\Foundation\Tenancy\Jobs\Middleware\RestoreTenantContext;
use Liberu\Foundation\Tenancy\TenantContext;

trait TenantAware
{
    public ?int $tenantId = null;

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RestoreTenantContext()];
    }

    protected function captureTenant(): void
    {
        $this->tenantId = TenantContext::currentId();
    }
}
