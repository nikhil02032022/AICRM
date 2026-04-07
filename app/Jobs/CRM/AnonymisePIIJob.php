<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * AnonymisePIIJob — Fulfils DPDP Act right-to-erasure requests.
 *
 * BRD: CRM-CR-005 — Erasure requests anonymise PII within 30 days.
 *
 * Replaces PII fields on the specified model with deterministic
 * anonymised values while preserving aggregate analytics data.
 *
 * Full implementation attached in Phase 1 when the Lead model is available.
 * This shell allows the job to be referenced and dispatched from day one.
 *
 * Usage:
 *   AnonymisePIIJob::dispatch($lead)->onQueue('default');
 */
class AnonymisePIIJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly string $modelClass,
        public readonly int $modelId,
        public readonly int $requestedByUserId,
    ) {}

    public function handle(): void
    {
        // Full implementation in Phase 1 — Lead + Application models
        // Steps:
        // 1. Resolve model: $model = $this->modelClass::findOrFail($this->modelId)
        // 2. Replace PII fields with deterministic anonymised values
        // 3. Log anonymisation event to audit_logs
        // 4. Mark consent_records as erasure_completed
        throw new \RuntimeException(
            'AnonymisePIIJob: full implementation pending Phase 1 Lead model.'
        );
    }
}
