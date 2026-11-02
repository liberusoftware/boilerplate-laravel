<?php

namespace Liberu\Foundation\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Liberu\Foundation\Tenancy\TenantContext;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user() ?? $request->user();

        setPermissionsTeamId(TenantContext::forUser($user));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Octane/RoadRunner reuses workers across requests — never let a team
        // leak into the next request.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
