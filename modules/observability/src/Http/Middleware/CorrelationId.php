<?php

namespace Liberu\Foundation\Observability\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class CorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header('X-Correlation-ID');
        $id = is_string($incoming) && Str::isUuid($incoming) ? $incoming : (string) Str::uuid();
        Context::add('correlation_id', $id);
        $request->attributes->set('correlation_id', $id);
        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $id);

        return $response;
    }
}
