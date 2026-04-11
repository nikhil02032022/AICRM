<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LC-019 — Data required to initiate a manual lead merge
final readonly class MergeLeadsDTO
{
    public function __construct(
        /** UUID of the lead that will survive (retain its record) */
        public string $primaryLeadUuid,

        /** UUID of the lead that will be merged in and soft-deleted */
        public string $secondaryLeadUuid,

        public int $institutionId,

        /** ID of the User who initiated the merge */
        public int $initiatedById,
    ) {}
}
