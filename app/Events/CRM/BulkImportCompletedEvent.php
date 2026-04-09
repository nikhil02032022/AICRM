<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\LeadImportBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-012 — Fired when a bulk import job batch finishes (success or partial failure)
final class BulkImportCompletedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LeadImportBatch $batch,
        public readonly bool            $partialFailure,
    ) {}
}
