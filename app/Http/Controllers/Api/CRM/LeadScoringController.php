<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\ScoreOverrideDTO;
use App\DTOs\CRM\UpdateScoringConfigDTO;
use App\Http\Resources\CRM\AiLeadScoreResource;
use App\Http\Resources\CRM\AiSuggestionDecisionResource;
use App\Http\Resources\CRM\AiUsageLogResource;
use App\Http\Resources\CRM\AiMessageDraftResource;
use App\Http\Resources\CRM\AnomalyAlertResource;
use App\Http\Resources\CRM\CounsellorPriorityLeadResource;
use App\Http\Resources\CRM\ChurnFlagResource;
use App\Http\Resources\CRM\EnrolmentForecastResource;
use App\Http\Resources\CRM\LeadNbaRecommendationResource;
use App\Http\Resources\CRM\NbaJourneyResource;
use App\Http\Resources\CRM\SentimentFlagResource;
use App\Http\Requests\CRM\GenerateAiMessageDraftRequest;
use App\Http\Requests\CRM\GenerateAnomalyDetectionRequest;
use App\Http\Requests\CRM\GenerateEnrolmentForecastRequest;
use App\Http\Requests\CRM\GenerateNbaJourneyRequest;
use App\Http\Requests\CRM\StoreAiSuggestionDecisionRequest;
use App\Http\Requests\CRM\StoreScoreOverrideRequest;
use App\Http\Requests\CRM\UpdateScoringConfigRequest;
use App\Jobs\CRM\GenerateLeadAiMessageDraftJob;
use App\Jobs\CRM\RunAnomalyDetectionJob;
use App\Jobs\CRM\GenerateEnrolmentForecastJob;
use App\Jobs\CRM\GenerateDailyPriorityLeadListJob;
use App\Jobs\CRM\GenerateNbaJourneyJob;
use App\Jobs\CRM\RecalculateLeadSentimentJob;
use App\Jobs\CRM\RecalculateAiLeadScoreJob;
use App\Jobs\CRM\RecalculateLeadChurnRiskJob;
use App\Jobs\CRM\RecalculateLeadNbaJob;
use App\Http\Resources\CRM\ScoreOverrideResource;
use App\Http\Resources\CRM\ScoringConfigResource;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\AiMessageDraft;
use App\Models\CRM\AiUsageLog;
use App\Models\CRM\AnomalyAlert;
use App\Models\CRM\CounsellorPriorityLead;
use App\Models\CRM\ChurnFlag;
use App\Models\CRM\EnrolmentForecast;
use App\Models\CRM\InstitutionScoringConfig;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadNbaRecommendation;
use App\Models\CRM\NbaJourney;
use App\Models\CRM\SentimentFlag;
use App\Services\CRM\Scoring\LeadScoringService;
use App\Services\CRM\AI\AiSuggestionDecisionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

// BRD: CRM-LQ-001, CRM-LQ-005, CRM-LQ-007 — API controller for scoring (external integrations only)
final class LeadScoringController extends Controller
{
    public function __construct(
        private readonly LeadScoringService $scoringService,
        private readonly AiSuggestionDecisionService $aiSuggestionDecisionService,
    ) {}

    /**
     * Get the scoring config for the authenticated institution.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function config(Request $request): ScoringConfigResource
    {
        Gate::authorize('update', InstitutionScoringConfig::class);

        $config = $this->scoringService->getScoringConfig($request->user()->institution_id);

        return new ScoringConfigResource($config);
    }

    /**
     * Update the scoring configuration.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function updateConfig(UpdateScoringConfigRequest $request): ScoringConfigResource
    {
        $dto = UpdateScoringConfigDTO::fromArray($request->validated());
        $config = $this->scoringService->updateConfig($request->user()->institution_id, $dto);

        return new ScoringConfigResource($config);
    }

    /**
     * Apply a manual score override to a lead.
     * BRD: CRM-LQ-007
     */
    public function override(StoreScoreOverrideRequest $request, Lead $lead): JsonResponse
    {
        Gate::authorize('override', $lead);

        $dto = new ScoreOverrideDTO(
            leadUuid: $lead->uuid,
            overriddenScore: (int) $request->validated('override_score'),
            reason: (string) $request->validated('reason'),
            actorId: $request->user()->id,
        );

        $override = $this->scoringService->applyManualOverride($lead, $dto);

        return (new ScoreOverrideResource($override))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get the latest AI-assisted score snapshot for a lead.
     * BRD: CRM-LQ-003
     */
    public function aiScore(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $aiScore = AiLeadScore::query()
            ->where('lead_id', $lead->id)
            ->latest('calculated_at')
            ->first();

        if ($aiScore === null) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No AI score snapshot available yet.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new AiLeadScoreResource($aiScore->loadMissing('lead')),
            'message' => 'AI score snapshot fetched successfully.',
        ]);
    }

    /**
     * Trigger asynchronous AI score recalculation for a lead.
     * BRD: CRM-LQ-003
     */
    public function triggerAiRecalculation(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateAiLeadScoreJob::dispatch($lead->uuid);

        return response()->json([
            'success' => true,
            'data' => ['lead_uuid' => $lead->uuid],
            'message' => 'AI score recalculation queued successfully.',
        ], 202);
    }

    /**
     * Get the latest churn risk snapshot for a lead.
     * BRD: CRM-LQ-010
     */
    public function churnRisk(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $churnFlag = ChurnFlag::query()
            ->where('lead_id', $lead->id)
            ->latest('flagged_at')
            ->first();

        if ($churnFlag === null) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No churn risk snapshot available yet.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new ChurnFlagResource($churnFlag->loadMissing('lead')),
            'message' => 'Churn risk snapshot fetched successfully.',
        ]);
    }

    /**
     * Trigger asynchronous churn risk recalculation for a lead.
     * BRD: CRM-LQ-010
     */
    public function triggerChurnRecalculation(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateLeadChurnRiskJob::dispatch($lead->uuid);

        return response()->json([
            'success' => true,
            'data' => ['lead_uuid' => $lead->uuid],
            'message' => 'Churn risk recalculation queued successfully.',
        ], 202);
    }

    /**
     * Get the latest next best action recommendation snapshot for a lead.
     * BRD: CRM-AI-002
     */
    public function nextBestAction(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $recommendation = LeadNbaRecommendation::query()
            ->where('lead_id', $lead->id)
            ->latest('generated_at')
            ->first();

        if ($recommendation === null) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No next best action recommendation available yet.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new LeadNbaRecommendationResource($recommendation->loadMissing('lead')),
            'message' => 'Next best action recommendation fetched successfully.',
        ]);
    }

    /**
     * Trigger asynchronous next best action recommendation recalculation.
     * BRD: CRM-AI-002
     */
    public function triggerNbaRecalculation(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateLeadNbaJob::dispatch($lead->uuid);

        return response()->json([
            'success' => true,
            'data' => ['lead_uuid' => $lead->uuid],
            'message' => 'Next best action recalculation queued successfully.',
        ], 202);
    }

    /**
     * Get the latest AI-assisted communication draft for a lead by channel.
     * BRD: CRM-AI-003
     */
    public function aiMessageDraft(Lead $lead, Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $channel = in_array((string) $request->query('channel', 'email'), ['email', 'whatsapp'], true)
            ? (string) $request->query('channel', 'email')
            : 'email';

        $draft = AiMessageDraft::query()
            ->where('lead_id', $lead->id)
            ->where('channel', $channel)
            ->latest('generated_at')
            ->first();

        if ($draft === null) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No AI message draft available yet.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new AiMessageDraftResource($draft->loadMissing('lead')),
            'message' => 'AI message draft fetched successfully.',
        ]);
    }

    /**
     * Trigger asynchronous AI-assisted communication draft generation.
     * BRD: CRM-AI-003
     */
    public function triggerAiMessageDraft(GenerateAiMessageDraftRequest $request, Lead $lead): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $channel = (string) $request->validated('channel');
        GenerateLeadAiMessageDraftJob::dispatch($lead->uuid, $channel);

        return response()->json([
            'success' => true,
            'data' => [
                'lead_uuid' => $lead->uuid,
                'channel' => $channel,
            ],
            'message' => 'AI message draft generation queued successfully.',
        ], 202);
    }

    /**
     * Get the latest inbound sentiment snapshot for a lead.
     * BRD: CRM-AI-004
     */
    public function sentiment(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $flag = SentimentFlag::query()
            ->where('lead_id', $lead->id)
            ->latest('flagged_at')
            ->first();

        if ($flag === null) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No sentiment snapshot available yet.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new SentimentFlagResource($flag->loadMissing('lead')),
            'message' => 'Sentiment snapshot fetched successfully.',
        ]);
    }

    /**
     * Trigger asynchronous inbound sentiment analysis for a lead.
     * BRD: CRM-AI-004
     */
    public function triggerSentimentRecalculation(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateLeadSentimentJob::dispatch($lead->uuid);

        return response()->json([
            'success' => true,
            'data' => ['lead_uuid' => $lead->uuid],
            'message' => 'Sentiment recalculation queued successfully.',
        ], 202);
    }

    /**
     * Get daily AI-prioritised leads for the authenticated counsellor.
     * BRD: CRM-AI-005
     */
    public function priorityLeads(Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.view');

        $forDate = (string) $request->query('for_date', now()->toDateString());
        $entries = CounsellorPriorityLead::query()
            ->where('counsellor_id', $request->user()->id)
            ->whereDate('generated_for_date', $forDate)
            ->orderBy('priority_rank')
            ->with('lead')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CounsellorPriorityLeadResource::collection($entries),
            'message' => 'Priority lead list fetched successfully.',
            'meta' => [
                'generated_for_date' => $forDate,
                'count' => $entries->count(),
            ],
        ]);
    }

    /**
     * Trigger generation of daily AI-prioritised lead list for caller institution.
     * BRD: CRM-AI-005
     */
    public function triggerPriorityLeadGeneration(Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.edit');

        $forDate = (string) $request->input('for_date', now()->toDateString());
        GenerateDailyPriorityLeadListJob::dispatch((int) $request->user()->institution_id, $forDate);

        return response()->json([
            'success' => true,
            'data' => [
                'institution_id' => (int) $request->user()->institution_id,
                'generated_for_date' => $forDate,
            ],
            'message' => 'Daily priority lead generation queued successfully.',
        ], 202);
    }

    /**
     * Get programme-wise enrolment forecasts for a selected month.
     * BRD: CRM-AI-008
     */
    public function enrolmentForecasts(Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.view');

        $forMonth = (string) $request->query('for_month', now()->format('Y-m'));
        $month = \Carbon\Carbon::createFromFormat('Y-m', $forMonth)->startOfMonth();

        $forecasts = EnrolmentForecast::query()
            ->whereDate('generated_for_month', $month->toDateString())
            ->with('programme:id,name')
            ->orderByDesc('forecast_count')
            ->get();

        return response()->json([
            'success' => true,
            'data' => EnrolmentForecastResource::collection($forecasts),
            'message' => 'Enrolment forecasts fetched successfully.',
            'meta' => [
                'for_month' => $month->format('Y-m'),
                'count' => $forecasts->count(),
            ],
        ]);
    }

    /**
     * Trigger asynchronous generation of monthly enrolment forecasts.
     * BRD: CRM-AI-008
     */
    public function triggerEnrolmentForecastGeneration(GenerateEnrolmentForecastRequest $request): JsonResponse
    {
        Gate::authorize('crm.leads.edit');

        $forMonth = (string) ($request->validated('for_month') ?? now()->format('Y-m'));
        $month = \Carbon\Carbon::createFromFormat('Y-m', $forMonth)->startOfMonth()->toDateString();

        GenerateEnrolmentForecastJob::dispatch((int) $request->user()->institution_id, $month);

        return response()->json([
            'success' => true,
            'data' => [
                'institution_id' => (int) $request->user()->institution_id,
                'for_month' => $forMonth,
            ],
            'message' => 'Enrolment forecast generation queued successfully.',
        ], 202);
    }

    /**
     * Get detected anomaly alerts for the selected date.
     * BRD: CRM-AI-009
     */
    public function anomalyAlerts(Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.view');

        $forDate = (string) $request->query('for_date', now()->toDateString());
        $severity = (string) $request->query('severity', '');

        $query = AnomalyAlert::query()
            ->whereDate('detected_at', $forDate)
            ->orderByDesc('detected_at');

        if ($severity !== '') {
            $query->where('severity', $severity);
        }

        $alerts = $query->get();

        return response()->json([
            'success' => true,
            'data' => AnomalyAlertResource::collection($alerts),
            'message' => 'Anomaly alerts fetched successfully.',
            'meta' => [
                'for_date' => $forDate,
                'count' => $alerts->count(),
            ],
        ]);
    }

    /**
     * Trigger asynchronous anomaly detection job.
     * BRD: CRM-AI-009
     */
    public function triggerAnomalyDetection(GenerateAnomalyDetectionRequest $request): JsonResponse
    {
        Gate::authorize('crm.leads.edit');

        $validated = $request->validated();

        RunAnomalyDetectionJob::dispatch(
            institutionId: (int) $request->user()->institution_id,
            forDate: (string) ($validated['for_date'] ?? now()->toDateString()),
            windowDays: (int) ($validated['window_days'] ?? 7),
            baselineDays: (int) ($validated['baseline_days'] ?? 28),
            thresholdPercent: (int) ($validated['threshold_percent'] ?? 25),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'institution_id' => (int) $request->user()->institution_id,
                'for_date' => (string) ($validated['for_date'] ?? now()->toDateString()),
            ],
            'message' => 'Anomaly detection queued successfully.',
        ], 202);
    }

    /**
     * Get AI nurture journey suggestions for the selected date and segment.
     * BRD: CRM-AI-010
     */
    public function nbaJourneys(Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.view');

        $forDate = (string) $request->query('for_date', now()->toDateString());
        $segment = (string) $request->query('segment', '');

        $query = NbaJourney::query()
            ->whereDate('generated_for_date', $forDate)
            ->orderByDesc('confidence_score')
            ->orderByDesc('suggested_at');

        if ($segment !== '') {
            $query->where('segment_key', $segment);
        }

        $journeys = $query->get();

        return response()->json([
            'success' => true,
            'data' => NbaJourneyResource::collection($journeys),
            'message' => 'Nurture journey suggestions fetched successfully.',
            'meta' => [
                'for_date' => $forDate,
                'segment' => $segment !== '' ? $segment : 'all',
                'count' => $journeys->count(),
            ],
        ]);
    }

    /**
     * Trigger asynchronous AI nurture journey generation.
     * BRD: CRM-AI-010
     */
    public function triggerNbaJourneyGeneration(GenerateNbaJourneyRequest $request): JsonResponse
    {
        Gate::authorize('crm.leads.edit');

        $validated = $request->validated();
        $forDate = (string) ($validated['for_date'] ?? now()->toDateString());
        $segment = isset($validated['segment']) ? (string) $validated['segment'] : null;

        GenerateNbaJourneyJob::dispatch(
            institutionId: (int) $request->user()->institution_id,
            forDate: $forDate,
            segment: $segment,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'institution_id' => (int) $request->user()->institution_id,
                'for_date' => $forDate,
                'segment' => $segment ?? 'all',
            ],
            'message' => 'Nurture journey generation queued successfully.',
        ], 202);
    }

    /**
     * Persist human Accept/Edit/Dismiss decision over AI-generated suggestion.
     * BRD: CRM-AI-011
     */
    public function storeAiSuggestionDecision(StoreAiSuggestionDecisionRequest $request): JsonResponse
    {
        Gate::authorize('crm.leads.edit');

        $decision = $this->aiSuggestionDecisionService->record(
            institutionId: (int) $request->user()->institution_id,
            actorId: (int) $request->user()->id,
            validated: $request->validated(),
        );

        return response()->json([
            'success' => true,
            'data' => new AiSuggestionDecisionResource($decision->loadMissing(['lead', 'actor'])),
            'message' => 'AI suggestion decision recorded successfully.',
        ], 201);
    }

    /**
     * Get AI usage logs for audit and compliance visibility.
     * BRD: CRM-AI-012
     */
    public function aiUsageLogs(Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.view');

        $featureKey = (string) $request->query('feature_key', '');
        $action = (string) $request->query('action', '');
        $fromDate = (string) $request->query('from_date', '');
        $toDate = (string) $request->query('to_date', '');

        $query = AiUsageLog::query()
            ->latest('occurred_at')
            ->with(['lead:id,uuid', 'actor:id,name']);

        if ($featureKey !== '') {
            $query->where('feature_key', $featureKey);
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($fromDate !== '') {
            $query->whereDate('occurred_at', '>=', $fromDate);
        }

        if ($toDate !== '') {
            $query->whereDate('occurred_at', '<=', $toDate);
        }

        $logs = $query->limit(200)->get();

        return response()->json([
            'success' => true,
            'data' => AiUsageLogResource::collection($logs),
            'message' => 'AI usage logs fetched successfully.',
            'meta' => ['count' => $logs->count()],
        ]);
    }
}
