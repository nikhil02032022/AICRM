<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api;

use App\Enums\CRM\Alumni\NpsSnapshotSource;
use App\Http\Controllers\Controller;
use App\Services\CRM\Alumni\AlumniNpsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-AL-004 — Webhook endpoint for automated NPS push from A2A Alumni module
// Protected by auth:sanctum middleware — not publicly accessible
final class AlumniNpsSyncController extends Controller
{
    public function __construct(
        private readonly AlumniNpsService $service,
    ) {}

    /**
     * POST /api/crm/v1/alumni/nps-sync
     *
     * Accepts NPS snapshot payload from A2A Alumni module and persists it.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution_id'   => ['required', 'integer', 'exists:institutions,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'programme_id'     => ['nullable', 'integer', 'exists:crm_programmes,id'],
            'promoters_pct'    => ['required', 'numeric', 'min:0', 'max:100'],
            'neutrals_pct'     => ['required', 'numeric', 'min:0', 'max:100'],
            'detractors_pct'   => ['required', 'numeric', 'min:0', 'max:100'],
            'survey_date'      => ['required', 'date'],
        ]);

        $validated['source'] = NpsSnapshotSource::Webhook->value;

        $snapshot = $this->service->storeSnapshot($validated);

        return response()->json([
            'success'    => true,
            'message'    => 'NPS snapshot recorded.',
            'nps_score'  => $snapshot->nps_score,
            'survey_date' => $snapshot->survey_date->toDateString(),
        ], 201);
    }
}
