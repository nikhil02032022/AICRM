<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\ForecastGeneratedEvent;
use App\Models\CRM\Institution;
use App\Services\CRM\AI\ForecastingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-008 — Async monthly enrolment forecast generation per institution
final class GenerateEnrolmentForecastJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly ?int $institutionId = null,
        public readonly ?string $forMonth = null,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return 'enrolment-forecast:'.($this->institutionId ?? 'all').':'.($this->forMonth ?? now()->startOfMonth()->toDateString());
    }

    public function handle(ForecastingService $service): void
    {
        $month = Carbon::parse($this->forMonth ?? now()->startOfMonth()->toDateString())->startOfMonth();

        $institutionIds = $this->institutionId !== null
            ? collect([$this->institutionId])
            : Institution::query()->where('is_active', true)->pluck('id');

        foreach ($institutionIds as $institutionId) {
            $rows = $service->generateForInstitution((int) $institutionId, $month);

            Log::info('Enrolment forecast generated', [
                'institution_id' => (int) $institutionId,
                'generated_for_month' => $month->toDateString(),
                'records' => $rows->count(),
            ]);

            ForecastGeneratedEvent::dispatch((int) $institutionId, $month->toDateString(), $rows->count());
        }
    }
}
