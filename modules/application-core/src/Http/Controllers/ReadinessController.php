<?php

namespace Liberu\Foundation\ApplicationCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Liberu\Foundation\ApplicationCore\Health\ReadinessRegistry;

final class ReadinessController
{
    public function __invoke(ReadinessRegistry $registry): JsonResponse
    {
        return response()->json(['status' => $registry->ready() ? 'ready' : 'unavailable', 'checks' => $registry->report(), 'release' => config('application-core.release')], $registry->ready() ? 200 : 503);
    }
}
