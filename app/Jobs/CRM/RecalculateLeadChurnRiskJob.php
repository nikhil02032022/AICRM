<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\LeadChurnFlaggedEvent;
use App\Jobs\CRM\RecalculateLeadNbaJob;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\ChurnDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LQ-010 — Async predictive churn risk recalculation job
final class RecalculateLeadChurnRiskJob implements ShouldBeUnique, ShouldQueue
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
        return "recalc-churn-risk:{$this->leadUuid}";
    }

    public function handle(ChurnDetectionService $service): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            return;
        }

        $churnFlag = $service->calculateAndPersist($lead);

        Log::info('Lead churn risk recalculated', [
            'lead_uuid' => $this->leadUuid,
            'churn_flag_uuid' => $churnFlag->uuid,
            'risk_level' => $churnFlag->risk_level->value,
        ]);

        LeadChurnFlaggedEvent::dispatch($churnFlag);

        // BRD: CRM-AI-002 — Refresh next-best-action guidance after churn signal update.
        RecalculateLeadNbaJob::dispatch($lead->uuid);
    }
}
