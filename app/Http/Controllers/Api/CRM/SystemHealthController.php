<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Services\CRM\Admin\SystemHealthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-SA-011 — System health monitoring API for external consumers (mobile, ERP dashboard)
class SystemHealthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SystemHealthService $service,
    ) {}

    /**
     * GET /api/v1/crm/admin/system-health
     * Returns current health snapshot for all 8 monitored components.
     * Super-admin or system-monitor role required.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('crm.admin.system-health.view');

        $snapshot = $this->service->getLatestSnapshot();

        return $this->success(data: $snapshot);
    }

    /**
     * GET /api/v1/crm/admin/system-health/{component}/history
     * Returns 24-hour trend data for a specific component.
     */
    public function history(Request $request, string $component): JsonResponse
    {
        $this->authorize('crm.admin.system-health.view');

        $history = $this->service->getHistory($component);

        return $this->success(data: $history);
    }
}
