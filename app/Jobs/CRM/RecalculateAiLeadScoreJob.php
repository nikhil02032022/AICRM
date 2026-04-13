<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\LeadAiScoreCalculatedEvent;
use App\Jobs\CRM\RecalculateLeadChurnRiskJob;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\AiLeadScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LQ-003 — Async AI-assisted score recalculation job
final class RecalculateAiLeadScoreJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly string $leadUuid,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return "recalc-ai-score:{$this->leadUuid}";
    }

    public function handle(AiLeadScoringService $service): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            return;
        }

        $aiScore = $service->calculateAndPersist($lead);

        Log::info('AI lead score recalculated', [
            'lead_uuid' => $this->leadUuid,
            'ai_score_uuid' => $aiScore->uuid,
            'model_version' => $aiScore->model_version,
        ]);

        LeadAiScoreCalculatedEvent::dispatch($aiScore);

        // BRD: CRM-LQ-010 — Recompute churn risk after fresh AI scoring is available.
        RecalculateLeadChurnRiskJob::dispatch($lead->uuid);
    }
}
