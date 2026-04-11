<?php

declare(strict_types=1);

namespace App\Services\CRM\Lead;

use App\DTOs\CRM\MergeLeadsDTO;
use App\Jobs\CRM\MergeLeadsJob;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-019 — Orchestrates the merge dispatch after all pre-flight validations pass
// Heavy data-transfer work is delegated to the async MergeLeadsJob.
final class LeadMergeService
{
    /**
     * Validate and dispatch a lead merge job.
     *
     * Pre-conditions (must be true before calling):
     * - Actor has crm.leads.merge permission (checked by gate/policy upstream)
     * - Both leads belong to the same institution as the actor
     * - Neither lead is already merged or soft-deleted
     *
     * @throws \DomainException when pre-conditions are violated
     */
    public function dispatch(Lead $primary, Lead $secondary, User $actor): string
    {
        if ($primary->uuid === $secondary->uuid) {
            throw new \DomainException('A lead cannot be merged with itself.');
        }

        if ($primary->institution_id !== $secondary->institution_id) {
            throw new \DomainException('Leads from different institutions cannot be merged.');
        }

        if ($primary->trashed() || $secondary->trashed()) {
            throw new \DomainException('Soft-deleted leads cannot be merged.');
        }

        if ($primary->isMerged() || $secondary->isMerged()) {
            throw new \DomainException('A lead that has already been merged cannot be merged again.');
        }

        $dto = new MergeLeadsDTO(
            primaryLeadUuid: $primary->uuid,
            secondaryLeadUuid: $secondary->uuid,
            institutionId: $primary->institution_id,
            initiatedById: $actor->id,
        );

        MergeLeadsJob::dispatch($dto);

        Log::info('Lead merge dispatched.', [
            'primary_uuid' => $primary->uuid,
            'secondary_uuid' => $secondary->uuid,
            'actor_id' => $actor->id,
        ]);

        return "merge:{$primary->uuid}:{$secondary->uuid}";
    }
}
