<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\SystemHealthComponent;
use App\Http\Controllers\Controller;
use App\Models\CRM\SystemHealthLog;
use App\Services\CRM\Admin\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-SA-011 — Web controller: real-time system health monitoring dashboard
final class SystemHealthWebController extends Controller
{
    public function __construct(
        private readonly SystemHealthService $service,
    ) {}

    // BRD: CRM-SA-011 — Render the health dashboard with latest probe snapshot
    public function index(): View
    {
        Gate::authorize('crm.admin.system-health.view');

        $snapshot = $this->service->getLatestSnapshot();

        return view('crm.admin.system-health.index', [
            'snapshot'           => $snapshot,
            'componentOptions'   => SystemHealthComponent::optionsForSelect(),
        ]);
    }

    // BRD: CRM-SA-011 — JSON polling endpoint for live refresh (Alpine.js fetch)
    public function poll(): JsonResponse
    {
        Gate::authorize('crm.admin.system-health.view');

        return response()->json([
            'success' => true,
            'data'    => $this->service->getLatestSnapshot(),
        ]);
    }

    // BRD: CRM-SA-011 — Historical trend data for a specific component (Chart.js)
    public function history(Request $request, string $component): JsonResponse
    {
        Gate::authorize('crm.admin.system-health.view');

        $hours   = (int) $request->query('hours', 24);
        $history = $this->service->getHistory($component, min($hours, 168));

        return response()->json([
            'success' => true,
            'data'    => $history,
        ]);
    }
}
