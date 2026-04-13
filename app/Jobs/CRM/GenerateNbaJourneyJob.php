<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\NbaJourneySuggestedEvent;
use App\Models\CRM\Institution;
use App\Services\CRM\AI\NbaJourneyService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-010 — Async generation of segment-wise nurture journey suggestions per institution
final class GenerateNbaJourneyJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly ?int $institutionId = null,
        public readonly ?string $forDate = null,
        public readonly ?string $segment = null,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return 'nba-journey:'.($this->institutionId ?? 'all').':'.($this->forDate ?? now()->toDateString()).':'.($this->segment ?? 'all');
    }

    public function handle(NbaJourneyService $service): void
    {
        $forDate = Carbon::parse($this->forDate ?? now()->toDateString());

        $institutionIds = $this->institutionId !== null
            ? collect([$this->institutionId])
            : Institution::query()->where('is_active', true)->pluck('id');

        foreach ($institutionIds as $institutionId) {
            $journeys = $service->generateForInstitution((int) $institutionId, $forDate, $this->segment);

            Log::info('NBA journey suggestion generation completed', [
                'institution_id' => (int) $institutionId,
                'for_date' => $forDate->toDateString(),
                'segment' => $this->segment,
                'suggestions' => $journeys->count(),
            ]);

            foreach ($journeys as $journey) {
                NbaJourneySuggestedEvent::dispatch($journey);
            }
        }
    }
}
