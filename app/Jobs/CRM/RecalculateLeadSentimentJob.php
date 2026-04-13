<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\LeadSentimentFlaggedEvent;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\SentimentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-004 — Async inbound sentiment recalculation per lead
final class RecalculateLeadSentimentJob implements ShouldBeUnique, ShouldQueue
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
        return "recalc-sentiment:{$this->leadUuid}";
    }

    public function handle(SentimentAnalysisService $service): void
    {
        $lead = Lead::withoutGlobalScopes()->where('uuid', $this->leadUuid)->first();

        if ($lead === null) {
            return;
        }

        $flag = $service->analyzeAndPersist($lead);

        Log::info('Lead sentiment recalculated', [
            'lead_uuid' => $this->leadUuid,
            'sentiment_flag_uuid' => $flag->uuid,
            'sentiment_label' => $flag->sentiment_label?->value,
            'is_urgent' => $flag->is_urgent,
        ]);

        LeadSentimentFlaggedEvent::dispatch($flag);
    }
}
