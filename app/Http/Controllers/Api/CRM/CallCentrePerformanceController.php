<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Services\CRM\Communication\CallCentrePerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-TC-007 — Call centre performance dashboard API endpoint
final class CallCentrePerformanceController extends Controller
{
    public function __construct(
        private readonly CallCentrePerformanceService $performanceService,
    ) {}

    /**
     * Retrieve call centre performance metrics (API).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function performance(Request $request): JsonResponse
    {
        $this->authorize('crm.voice.performance');

        $validated = $request->validate([
            'from_date' => ['required', 'date', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $institutionId = (int) $request->user()->institution_id;

        $report = $this->performanceService->buildPerformanceReport(
            $institutionId,
            (string) $validated['from_date'],
            (string) $validated['to_date'],
            isset($validated['agent_id']) ? (int) $validated['agent_id'] : null,
        );

        $volumeTrend = $this->performanceService->getDailyCallVolumeTrend(
            $institutionId,
            (string) $validated['from_date'],
            (string) $validated['to_date'],
        );

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'volume_trend' => $volumeTrend,
            ],
            'message' => 'Performance report retrieved successfully',
        ]);
    }
}
