<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Events\CRM\ScoreChangedEvent;
use App\Models\CRM\Lead;
use App\Services\CRM\Scoring\LeadScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LQ-001, CRM-LQ-004 — Recalculate lead score on every qualifying activity
final class RecalculateLeadScoreJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly string $leadUuid,
    ) {
        // BRD: CRM-LQ-004 — Dedicated queue keeps scoring isolated from other operations
        $this->onQueue('crm-scoring');
    }

    /** Unique key prevents duplicate score recalculations queuing for the same lead. */
    public function uniqueId(): string
    {
        return "recalc-score:{$this->leadUuid}";
    }

    /**
     * Method injection resolves the service from the container (testable).
     * BRD: CRM-LQ-001 — Full configurable scoring engine replaces the previous stub.
     * BRD: CRM-LQ-003 — AI scoring engine augments this in Phase 2 (Should Have).
     */
    public function handle(LeadScoringService $scoringService): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->with('programmeInterests')
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            // Lead deleted before job ran — safe to discard
            return;
        }

        // BRD: CRM-LQ-007 — Do NOT auto-recalculate if score was manually overridden
        if ($lead->score_manually_overridden) {
            return;
        }

        $previousScore       = $lead->lead_score;
        $previousTemperature = $lead->temperature;

        $config   = $scoringService->getScoringConfig($lead->institution_id);
        $newScore = $scoringService->calculateScore($lead, $config);

        // BRD: CRM-LQ-005 — Temperature derived from institution-configured thresholds
        $newTemperature = $scoringService->deriveTemperature($newScore, $config);

        $lead->update([
            'lead_score'  => $newScore,
            'temperature' => $newTemperature->value,
        ]);

        // BRD: CRM-CR-002 — No PII in logs
        Log::info('Lead score recalculated', [
            'lead_uuid' => $this->leadUuid,
            'old_score' => $previousScore,
            'new_score' => $newScore,
        ]);

        // BRD: CRM-LQ-006 — Fire events only when values actually change
        if ($newScore !== $previousScore) {
            ScoreChangedEvent::dispatch($lead, $previousScore, $newScore);
        }

        if ($newTemperature !== $previousTemperature) {
            LeadTemperatureChangedEvent::dispatch($lead, $previousTemperature, $newTemperature);
        }
    }
}
