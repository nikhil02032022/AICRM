<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\AgentPortal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use App\Services\CRM\Agents\AgentReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-003 — Agent portal dashboard with KPI stats
final class AgentPortalDashboardController extends Controller
{
    public function __construct(private readonly AgentReportService $reportService) {}

    public function index(Request $request): View
    {
        /** @var Agent $agent */
        $agent   = $request->attributes->get('agent');
        $metrics = $this->reportService->forAgent($agent);

        $recentLeads = $agent->leads()
            ->with('assignedCounsellor')
            ->latest()
            ->limit(5)
            ->get();

        return view('agent-portal.dashboard', compact('agent', 'metrics', 'recentLeads'));
    }
}
