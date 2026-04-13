<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\AnomalyDetectedEvent;
use App\Models\CRM\Institution;
use App\Services\CRM\AI\AnomalyDetectionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-009 — Async anomaly detection over rolling windows for institutional funnel metrics
final class RunAnomalyDetectionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly ?int $institutionId = null,
        public readonly ?string $forDate = null,
        public readonly int $windowDays = 7,
        public readonly int $baselineDays = 28,
        public readonly int $thresholdPercent = 25,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return 'anomaly-detection:'.($this->institutionId ?? 'all').':'.($this->forDate ?? now()->toDateString());
    }

    public function handle(AnomalyDetectionService $service): void
    {
        $forDate = Carbon::parse($this->forDate ?? now()->toDateString());

        $institutionIds = $this->institutionId !== null
            ? collect([$this->institutionId])
            : Institution::query()->where('is_active', true)->pluck('id');

        foreach ($institutionIds as $institutionId) {
            $alerts = $service->detectForInstitution(
                institutionId: (int) $institutionId,
                forDate: $forDate,
                windowDays: $this->windowDays,
                baselineDays: $this->baselineDays,
                thresholdPercent: $this->thresholdPercent,
            );

            Log::info('Anomaly detection completed', [
                'institution_id' => (int) $institutionId,
                'for_date' => $forDate->toDateString(),
                'window_days' => $this->windowDays,
                'baseline_days' => $this->baselineDays,
                'threshold_percent' => $this->thresholdPercent,
                'alerts' => $alerts->count(),
            ]);

            foreach ($alerts as $alert) {
                AnomalyDetectedEvent::dispatch($alert);
            }
        }
    }
}
