<?php

namespace Liberu\Foundation\Tenancy\Jobs\Middleware;

use Liberu\Foundation\Tenancy\TenantContext;

class RestoreTenantContext
{
    public function handle(object $job, callable $next): mixed
    {
        TenantContext::set($job->tenantId ?? null);

        try {
            return $next($job);
        } finally {
            TenantContext::clear();
        }
    }
}
