<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\ScoreOverrideDTO;
use App\DTOs\CRM\UpdateScoringConfigDTO;
use App\Http\Requests\CRM\StoreScoreOverrideRequest;
use App\Http\Requests\CRM\UpdateScoringConfigRequest;
use App\Http\Resources\CRM\ScoringConfigResource;
use App\Http\Resources\CRM\ScoreOverrideResource;
use App\Models\CRM\Lead;
use App\Services\CRM\Scoring\LeadScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

// BRD: CRM-LQ-001, CRM-LQ-005, CRM-LQ-007 — API controller for scoring (external integrations only)
final class LeadScoringController extends Controller
{
    public function __construct(
        private readonly LeadScoringService $scoringService,
    ) {}

    /**
     * Get the scoring config for the authenticated institution.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function config(Request $request): ScoringConfigResource
    {
        $this->authorize('update', \App\Models\CRM\InstitutionScoringConfig::class);

        $config = $this->scoringService->getScoringConfig($request->user()->institution_id);

        return new ScoringConfigResource($config);
    }

    /**
     * Update the scoring configuration.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function updateConfig(UpdateScoringConfigRequest $request): ScoringConfigResource
    {
        $dto    = UpdateScoringConfigDTO::fromArray($request->validated());
        $config = $this->scoringService->updateConfig($request->user()->institution_id, $dto);

        return new ScoringConfigResource($config);
    }

    /**
     * Apply a manual score override to a lead.
     * BRD: CRM-LQ-007
     */
    public function override(StoreScoreOverrideRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('override', $lead);

        $dto = new ScoreOverrideDTO(
            leadUuid:        $lead->uuid,
            overriddenScore: (int) $request->validated('override_score'),
            reason:          (string) $request->validated('reason'),
            actorId:         $request->user()->id,
        );

        $override = $this->scoringService->applyManualOverride($lead, $dto);

        return (new ScoreOverrideResource($override))
            ->response()
            ->setStatusCode(201);
    }
}
