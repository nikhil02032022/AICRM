<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Services\CRM\Communication\CallCentrePerformanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

// BRD: CRM-TC-007 — Call centre performance dashboard web controller
final class CallCentrePerformanceWebController extends Controller
{
    public function __construct(
        private readonly CallCentrePerformanceService $performanceService,
    ) {}

    /**
     * Display call centre performance dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $this->authorize('crm.voice.performance');

        $institutionId = (int) $request->user()->institution_id;

        $fromDate = (string) ($request->query('from_date') ?? now()->startOfMonth()->format('Y-m-d'));
        $toDate = (string) ($request->query('to_date') ?? now()->format('Y-m-d'));
        $agentId = $request->query('agent_id') ? (int) $request->query('agent_id') : null;

        $report = $this->performanceService->buildPerformanceReport(
            $institutionId,
            $fromDate,
            $toDate,
            $agentId,
        );

        $volumeTrend = $this->performanceService->getDailyCallVolumeTrend(
            $institutionId,
            $fromDate,
            $toDate,
        );

        return view('crm.communication.voice.performance', [
            'report' => $report,
            'volumeTrend' => $volumeTrend,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedAgentId' => $agentId,
        ]);
    }
}
