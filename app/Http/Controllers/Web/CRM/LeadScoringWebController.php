<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\ScoreOverrideDTO;
use App\DTOs\CRM\UpdateScoringConfigDTO;
use App\Jobs\CRM\RecalculateAiLeadScoreJob;
use App\Jobs\CRM\GenerateEnrolmentForecastJob;
use App\Jobs\CRM\GenerateLeadAiMessageDraftJob;
use App\Jobs\CRM\GenerateDailyPriorityLeadListJob;
use App\Jobs\CRM\GenerateNbaJourneyJob;
use App\Jobs\CRM\RecalculateLeadSentimentJob;
use App\Jobs\CRM\RecalculateLeadChurnRiskJob;
use App\Jobs\CRM\RecalculateLeadNbaJob;
use App\Jobs\CRM\RunAnomalyDetectionJob;
use App\Http\Requests\CRM\GenerateAiMessageDraftRequest;
use App\Http\Requests\CRM\GenerateAnomalyDetectionRequest;
use App\Http\Requests\CRM\GenerateEnrolmentForecastRequest;
use App\Http\Requests\CRM\GenerateNbaJourneyRequest;
use App\Http\Requests\CRM\StoreAiSuggestionDecisionRequest;
use App\Http\Requests\CRM\StoreScoreOverrideRequest;
use App\Http\Requests\CRM\UpdateScoringConfigRequest;
use App\Http\Resources\CRM\AiLeadScoreResource;
use App\Http\Resources\CRM\AiMessageDraftResource;
use App\Http\Resources\CRM\AiUsageLogResource;
use App\Http\Resources\CRM\AnomalyAlertResource;
use App\Http\Resources\CRM\CounsellorPriorityLeadResource;
use App\Http\Resources\CRM\ChurnFlagResource;
use App\Http\Resources\CRM\EnrolmentForecastResource;
use App\Http\Resources\CRM\LeadNbaRecommendationResource;
use App\Http\Resources\CRM\NbaJourneyResource;
use App\Http\Resources\CRM\SentimentFlagResource;
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
use Illuminate\Http\JsonResponse;
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
        private readonly AiSuggestionDecisionService $aiSuggestionDecisionService,
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

    /**
     * BRD: CRM-LQ-003 — Return latest AI score snapshot for lead detail view.
     */
    public function aiScore(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $latest = AiLeadScore::query()
            ->where('lead_id', $lead->id)
            ->latest('calculated_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latest ? new AiLeadScoreResource($latest->loadMissing('lead')) : null,
        ]);
    }

    /**
     * BRD: CRM-LQ-003 — Queue asynchronous AI scoring for the lead.
     */
    public function triggerAiRecalculation(Lead $lead): RedirectResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateAiLeadScoreJob::dispatch($lead->uuid);

        return back()->with('success', 'AI score recalculation queued successfully.');
    }

    /**
     * BRD: CRM-LQ-010 — Return latest churn risk snapshot for lead detail view.
     */
    public function churnRisk(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $latest = ChurnFlag::query()
            ->where('lead_id', $lead->id)
            ->latest('flagged_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latest ? new ChurnFlagResource($latest->loadMissing('lead')) : null,
        ]);
    }

    /**
     * BRD: CRM-LQ-010 — Queue asynchronous churn risk scoring for the lead.
     */
    public function triggerChurnRecalculation(Lead $lead): RedirectResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateLeadChurnRiskJob::dispatch($lead->uuid);

        return back()->with('success', 'Churn risk recalculation queued successfully.');
    }

    /**
     * BRD: CRM-AI-002 — Return latest next best action recommendation snapshot.
     */
    public function nextBestAction(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $latest = LeadNbaRecommendation::query()
            ->where('lead_id', $lead->id)
            ->latest('generated_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latest ? new LeadNbaRecommendationResource($latest->loadMissing('lead')) : null,
        ]);
    }

    /**
     * BRD: CRM-AI-002 — Queue asynchronous next best action generation for lead.
     */
    public function triggerNbaRecalculation(Lead $lead): RedirectResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateLeadNbaJob::dispatch($lead->uuid);

        return back()->with('success', 'Next best action recalculation queued successfully.');
    }

    /**
     * BRD: CRM-AI-003 — Return latest AI communication draft by channel.
     */
    public function aiMessageDraft(Lead $lead, Request $request): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $channel = in_array((string) $request->query('channel', 'email'), ['email', 'whatsapp'], true)
            ? (string) $request->query('channel', 'email')
            : 'email';

        $latest = AiMessageDraft::query()
            ->where('lead_id', $lead->id)
            ->where('channel', $channel)
            ->latest('generated_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latest ? new AiMessageDraftResource($latest->loadMissing('lead')) : null,
        ]);
    }

    /**
     * BRD: CRM-AI-003 — Queue asynchronous AI communication draft generation for lead.
     */
    public function triggerAiMessageDraft(GenerateAiMessageDraftRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('crm.communication.send');

        $channel = (string) $request->validated('channel');
        GenerateLeadAiMessageDraftJob::dispatch($lead->uuid, $channel);

        return back()->with('success', 'AI '.$channel.' draft generation queued successfully.');
    }

    /**
     * BRD: CRM-AI-004 — Return latest inbound sentiment snapshot for lead detail view.
     */
    public function sentiment(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view', $lead);

        $latest = SentimentFlag::query()
            ->where('lead_id', $lead->id)
            ->latest('flagged_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latest ? new SentimentFlagResource($latest->loadMissing('lead')) : null,
        ]);
    }

    /**
     * BRD: CRM-AI-004 — Queue asynchronous inbound sentiment recalculation for lead.
     */
    public function triggerSentimentRecalculation(Lead $lead): RedirectResponse
    {
        Gate::authorize('crm.leads.edit', $lead);

        RecalculateLeadSentimentJob::dispatch($lead->uuid);

        return back()->with('success', 'Inbound sentiment recalculation queued successfully.');
    }

    /**
     * BRD: CRM-AI-005 — Daily AI-prioritised lead list view for authenticated counsellor.
     */
    public function priorityLeads(Request $request): View
    {
        Gate::authorize('crm.leads.view');

        $forDate = (string) $request->query('for_date', now()->toDateString());

        $entries = CounsellorPriorityLead::query()
            ->where('counsellor_id', $request->user()->id)
            ->whereDate('generated_for_date', $forDate)
            ->orderBy('priority_rank')
            ->with('lead:id,uuid,first_name,last_name,mobile,lead_score')
            ->get();

        return view('crm.scoring.priority-leads', [
            'forDate' => $forDate,
            'entries' => CounsellorPriorityLeadResource::collection($entries),
        ]);
    }

    /**
     * BRD: CRM-AI-005 — Queue daily priority list generation for caller institution.
     */
    public function triggerPriorityLeadGeneration(Request $request): RedirectResponse
    {
        Gate::authorize('crm.leads.edit');

        $forDate = (string) $request->input('for_date', now()->toDateString());
        GenerateDailyPriorityLeadListJob::dispatch((int) $request->user()->institution_id, $forDate);

        return back()->with('success', 'Daily priority lead generation queued successfully.');
    }

    /**
     * BRD: CRM-AI-008 — Monthly programme-wise enrolment forecast dashboard.
     */
    public function enrolmentForecasts(Request $request): View
    {
        Gate::authorize('crm.leads.view');

        $forMonth = (string) $request->query('for_month', now()->format('Y-m'));
        $month = \Carbon\Carbon::createFromFormat('Y-m', $forMonth)->startOfMonth();

        $rows = EnrolmentForecast::query()
            ->whereDate('generated_for_month', $month->toDateString())
            ->with('programme:id,name')
            ->orderByDesc('forecast_count')
            ->get();

        $resources = EnrolmentForecastResource::collection($rows);

        return view('crm.scoring.forecast-dashboard', [
            'forMonth' => $forMonth,
            'rows' => $resources,
        ]);
    }

    /**
     * BRD: CRM-AI-008 — Queue monthly forecast generation for caller institution.
     */
    public function triggerEnrolmentForecastGeneration(GenerateEnrolmentForecastRequest $request): RedirectResponse
    {
        Gate::authorize('crm.leads.edit');

        $forMonth = (string) ($request->validated('for_month') ?? now()->format('Y-m'));
        $month = \Carbon\Carbon::createFromFormat('Y-m', $forMonth)->startOfMonth()->toDateString();

        GenerateEnrolmentForecastJob::dispatch((int) $request->user()->institution_id, $month);

        return back()->with('success', 'Enrolment forecast generation queued successfully.');
    }

    /**
     * BRD: CRM-AI-009 — Anomaly alert dashboard for funnel drop-off detection.
     */
    public function anomalyAlerts(Request $request): View
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

        $rows = $query->get();

        return view('crm.scoring.anomaly-alerts', [
            'forDate' => $forDate,
            'severity' => $severity,
            'rows' => AnomalyAlertResource::collection($rows),
        ]);
    }

    /**
     * BRD: CRM-AI-009 — Queue anomaly detection run for selected window.
     */
    public function triggerAnomalyDetection(GenerateAnomalyDetectionRequest $request): RedirectResponse
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

        return back()->with('success', 'Anomaly detection queued successfully.');
    }

    /**
     * BRD: CRM-AI-010 — Segment-wise AI nurture journey suggestion dashboard.
     */
    public function nbaJourneys(Request $request): View
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

        $rows = $query->get();

        return view('crm.scoring.nba-journey', [
            'forDate' => $forDate,
            'segment' => $segment,
            'rows' => NbaJourneyResource::collection($rows),
        ]);
    }

    /**
     * BRD: CRM-AI-010 — Queue nurture journey suggestion generation.
     */
    public function triggerNbaJourneyGeneration(GenerateNbaJourneyRequest $request): RedirectResponse
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

        return back()->with('success', 'Nurture journey generation queued successfully.');
    }

    /**
     * BRD: CRM-AI-011 — Record explicit human decision for AI suggestion cards.
     */
    public function storeAiSuggestionDecision(StoreAiSuggestionDecisionRequest $request): RedirectResponse
    {
        Gate::authorize('crm.leads.edit');

        $this->aiSuggestionDecisionService->record(
            institutionId: (int) $request->user()->institution_id,
            actorId: (int) $request->user()->id,
            validated: $request->validated(),
        );

        return back()->with('success', 'AI suggestion decision recorded successfully.');
    }

    /**
     * BRD: CRM-AI-012 — AI usage audit dashboard for compliance and traceability.
     */
    public function aiUsageLogs(Request $request): View
    {
        Gate::authorize('crm.leads.view');

        $featureKey = (string) $request->query('feature_key', '');
        $action = (string) $request->query('action', '');
        $fromDate = (string) $request->query('from_date', now()->subDays(7)->toDateString());
        $toDate = (string) $request->query('to_date', now()->toDateString());

        $query = AiUsageLog::query()
            ->with(['lead:id,uuid,first_name,last_name', 'actor:id,name'])
            ->latest('occurred_at');

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

        $rows = $query->limit(300)->get();

        return view('crm.scoring.ai-usage-logs', [
            'featureKey' => $featureKey,
            'action' => $action,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'rows' => AiUsageLogResource::collection($rows),
        ]);
    }
}
