<?php

declare(strict_types=1);

namespace App\Jobs\CRM\AI;

use App\Enums\CRM\AI\PredictionStatus;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\ConversionPredictionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-001 — Async Claude API conversion probability prediction; deduped per lead via Redis lock
final class RefreshConversionPredictionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly string $leadUuid,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return "convert-prediction:{$this->leadUuid}";
    }

    public function handle(ConversionPredictionService $service): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->first();

        if ($lead === null) {
            return;
        }

        // Institution-scoped Redis lock prevents concurrent duplicate predictions
        $lockKey = "predict-lock:{$lead->institution_id}:{$lead->id}";
        $lock    = Cache::lock($lockKey, 120);

        if (! $lock->get()) {
            Log::info('RefreshConversionPredictionJob: skipped — lock held', ['lead_uuid' => $this->leadUuid]);
            return;
        }

        try {
            AiLeadScore::withoutGlobalScopes()
                ->where('lead_id', $lead->id)
                ->latest('calculated_at')
                ->update(['prediction_status' => PredictionStatus::Processing]);

            $score = $service->predict($lead);

            Log::info('RefreshConversionPredictionJob: prediction completed', [
                'lead_uuid'      => $this->leadUuid,
                'score_uuid'     => $score->uuid,
                'probability'    => $score->conversion_probability,
                'confidence'     => $score->confidence_score,
                'status'         => $score->prediction_status?->value,
            ]);
        } finally {
            $lock->release();
        }
    }
}
