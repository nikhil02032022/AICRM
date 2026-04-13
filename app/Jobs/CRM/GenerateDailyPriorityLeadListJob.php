<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\Institution;
use App\Services\CRM\AI\PriorityLeadListService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-005 — Daily generation of counsellor priority lead list snapshots
final class GenerateDailyPriorityLeadListJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly ?int $institutionId = null,
        public readonly ?string $forDate = null,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return 'daily-priority-leads:'.($this->institutionId ?? 'all').':'.($this->forDate ?? now()->toDateString());
    }

    public function handle(PriorityLeadListService $service): void
    {
        $date = Carbon::parse($this->forDate ?? now()->toDateString());

        $institutionIds = $this->institutionId !== null
            ? collect([$this->institutionId])
            : Institution::query()->where('is_active', true)->pluck('id');

        foreach ($institutionIds as $institutionId) {
            $count = $service->generateForInstitution((int) $institutionId, $date);

            Log::info('Daily priority leads generated', [
                'institution_id' => (int) $institutionId,
                'generated_for_date' => $date->toDateString(),
                'records' => $count,
            ]);
        }
    }
}
