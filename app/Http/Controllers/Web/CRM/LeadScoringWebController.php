<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\ScoreOverrideDTO;
use App\DTOs\CRM\UpdateScoringConfigDTO;
use App\Http\Requests\CRM\StoreScoreOverrideRequest;
use App\Http\Requests\CRM\UpdateScoringConfigRequest;
use App\Models\CRM\InstitutionScoringConfig;
use App\Models\CRM\Lead;
use App\Services\CRM\Scoring\LeadScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-LQ-001, CRM-LQ-005, CRM-LQ-007, CRM-LQ-008 — Web controller for scoring config + overrides
final class LeadScoringWebController extends Controller
{
    public function __construct(
        private readonly LeadScoringService $scoringService,
    ) {}

    /**
     * Show the scoring configuration page for the authenticated user's institution.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function config(Request $request): View
    {
        Gate::authorize('update', InstitutionScoringConfig::class);

        $institutionId = $request->user()->institution_id;
        $config = $this->scoringService->getScoringConfig($institutionId);

        return view('crm.scoring.config', compact('config'));
    }

    /**
     * Save updated scoring weights and temperature thresholds.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function updateConfig(UpdateScoringConfigRequest $request): RedirectResponse
    {
        $dto = UpdateScoringConfigDTO::fromArray($request->validated());
        $this->scoringService->updateConfig($request->user()->institution_id, $dto);

        return redirect()
            ->route('crm.scoring.config')
            ->with('success', 'Scoring configuration saved successfully.');
    }

    /**
     * Apply a manual score override to a lead.
     * BRD: CRM-LQ-007
     */
    public function override(StoreScoreOverrideRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('override', $lead);

        $dto = new ScoreOverrideDTO(
            leadUuid: (string) $lead->uuid,
            overriddenScore: (int) $request->validated('override_score'),
            reason: (string) $request->validated('reason'),
            actorId: $request->user()->id,
        );

        $this->scoringService->applyManualOverride($lead, $dto);

        return redirect()
            ->route('crm.leads.show', $lead->uuid)
            ->with('success', 'Score override applied successfully.');
    }

    /**
     * Show the source quality scoring report.
     * BRD: CRM-LQ-008
     */
    public function sourceQualityReport(Request $request): View
    {
        Gate::authorize('viewReport', InstitutionScoringConfig::class);

        $report = $this->scoringService->getSourceQualityReport($request->user()->institution_id);

        return view('crm.scoring.source-quality', compact('report'));
    }
}
