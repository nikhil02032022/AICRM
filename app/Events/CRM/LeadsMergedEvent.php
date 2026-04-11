<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-019 — Fired after a lead merge completes successfully
final class LeadsMergedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        /** The surviving lead (primary) — contains all transferred data */
        public readonly Lead $primaryLead,

        /** The merged-in lead (secondary) — now soft-deleted with tombstone */
        public readonly Lead $secondaryLead,

        /** Count of activity records transferred to primary */
        public readonly int $mergedActivityCount,

        /** Count of sessions transferred to primary */
        public readonly int $mergedSessionCount,

        /** ID of the user who triggered the merge */
        public readonly int $initiatedById,
    ) {}
}
