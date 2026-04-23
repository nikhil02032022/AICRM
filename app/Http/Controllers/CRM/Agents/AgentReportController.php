<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Agents;

use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use App\Services\CRM\Agents\AgentReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-007 — Agent performance report: leads submitted, conversions, revenue, commissions
final class AgentReportController extends Controller
{
    public function __construct(private readonly AgentReportService $reportService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Agent::class);

        $filters = $request->only(['from', 'to', 'agent_id']);

        if (! empty($filters['agent_id'])) {
            $agent   = Agent::withoutGlobalScopes()->findOrFail($filters['agent_id']);
            $metrics = $this->reportService->forAgent($agent, $filters);
            $rows    = collect([array_merge(['agent' => $agent], $metrics)]);
        } else {
            $rows = $this->reportService->forInstitution(
                $request->user()->institution_id,
                $filters,
            );
        }

        $agents = Agent::withoutGlobalScopes()
            ->where('institution_id', $request->user()->institution_id)
            ->pluck('name', 'id');

        return view('crm.agents.report', compact('rows', 'agents', 'filters'));
    }
}
