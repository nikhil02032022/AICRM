<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CRM\System\HealthCheckService;
use Illuminate\Http\JsonResponse;

// NFR-AV-001 — Load balancer health check endpoint.
// Public, unauthenticated. Returns 200 on healthy, 503 on degraded.
// Never exposes stack traces, DB schema, or internal configuration.
final class HealthController extends Controller
{
    public function __invoke(HealthCheckService $healthService): JsonResponse
    {
        $result = $healthService->check();

        $status = $result['status'] === 'ok' ? 200 : 503;

        return response()->json($result, $status);
    }
}
