<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\LeadNbaRecommendedEvent;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\NbaRecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-002 — Async next best action recommendation generation per lead
final class RecalculateLeadNbaJob implements ShouldBeUnique, ShouldQueue
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
        return "recalc-nba:{$this->leadUuid}";
    }

    public function handle(NbaRecommendationService $service): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            return;
        }

        $recommendation = $service->generateAndPersist($lead);

        Log::info('Lead next best action generated', [
            'lead_uuid' => $this->leadUuid,
            'recommendation_uuid' => $recommendation->uuid,
            'recommended_action' => $recommendation->recommended_action,
            'confidence_score' => $recommendation->confidence_score,
        ]);

        LeadNbaRecommendedEvent::dispatch($recommendation);
    }
}
