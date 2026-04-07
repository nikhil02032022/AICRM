<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-018 — Auto-detect duplicate leads on mobile/email match and flag them for counsellor review
final class DetectLeadDuplicatesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly string $leadUuid,
        public readonly int    $institutionId,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return "dedup:{$this->leadUuid}";
    }

    public function handle(): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $this->leadUuid)
            ->where('institution_id', $this->institutionId)
            ->first();

        if ($lead === null) {
            return;
        }

        // Encrypted mobile/email comparison happens at PHP model level
        // (see EloquentLeadRepository::findDuplicates for full implementation)
        $duplicates = Lead::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->where('uuid', '!=', $this->leadUuid)
            ->whereNull('deleted_at')
            ->get(['id', 'uuid', 'mobile', 'email', 'first_name', 'last_name'])
            ->filter(fn(Lead $l) => $l->mobile === $lead->mobile || ($lead->email !== null && $l->email === $lead->email));

        if ($duplicates->isNotEmpty()) {
            // BRD: CRM-LC-018 — Flag for counsellor attention (full dedup UI in Phase 1 Group D)
            Log::warning('Potential duplicate lead detected', [
                'lead_uuid'       => $this->leadUuid,
                'duplicate_count' => $duplicates->count(),
            ]);

            // TODO CRM-LC-018: dispatch DuplicateLeadFlaggedEvent when that event is built
        }
    }
}
