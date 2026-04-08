<?php

declare(strict_types=1);

namespace App\Services\CRM\Lead;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadStatus;
use App\Events\CRM\LeadCreatedEvent;
use App\Events\CRM\LeadStatusChangedEvent;
use App\Jobs\CRM\DetectLeadDuplicatesJob;
use App\Jobs\CRM\RecalculateLeadScoreJob;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Lead\LeadRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-011 — Central service for lead creation and lifecycle management
// BRD: CRM-LC-014 — Source field enforcement is in StoreLeadRequest; service trusts validated DTO
final class LeadService
{
    public function __construct(
        private readonly LeadRepositoryInterface $repository,
    ) {}

    /**
     * Create a new lead from a validated DTO.
     *
     * BRD: CRM-LC-011 — Manual lead creation by counsellors (desktop + mobile)
     * BRD: CRM-CR-001 — Consent fields captured and stored at creation time
     * BRD: CRM-LC-018 — Async duplicate detection dispatched after save
     */
    public function create(CreateLeadDTO $dto, Authenticatable $actor): Lead
    {
        /** @var \App\Models\User $actor */
        $lead = $this->repository->create($dto, $actor->institution_id);

        // Programme interests (optional at creation)
        if (! empty($dto->programmeIds)) {
            $this->repository->syncProgrammeInterests($lead, $dto->programmeIds);
        }

        // BRD: CRM-LC-011 — Auto-assign to creating counsellor if actor has that role
        if ($actor->hasRole('counsellor') && $lead->assigned_counsellor_id === null) {
            $this->repository->update($lead, ['assigned_counsellor_id' => $actor->id]);
        }

        // BRD: CRM-CR-002 — No PII in log messages
        Log::info('Lead created', [
            'lead_uuid'      => $lead->uuid,
            'institution_id' => $lead->institution_id,
            'actor_id'       => $actor->id,
        ]);

        LeadCreatedEvent::dispatch($lead);

        // Async: score and dedup (non-blocking)
        RecalculateLeadScoreJob::dispatch($lead->uuid);
        DetectLeadDuplicatesJob::dispatch($lead->uuid, $lead->institution_id);

        return $lead;
    }

    /**
     * Transition a lead to a new status with validation.
     *
     * BRD: CRM-LC-001 — Status transitions must follow allowed pipeline flow
     */
    public function transitionStatus(Lead $lead, LeadStatus $newStatus): Lead
    {
        $previousStatus = $lead->status;

        if (! $previousStatus->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Cannot transition lead from [{$previousStatus->label()}] to [{$newStatus->label()}]."
            );
        }

        $updated = $this->repository->update($lead, ['status' => $newStatus->value]);

        LeadStatusChangedEvent::dispatch($updated, $previousStatus, $newStatus);

        // Re-score on status change (engagement signal)
        RecalculateLeadScoreJob::dispatch($lead->uuid);

        return $updated;
    }

    /**
     * Update non-sensitive lead fields.
     *
     * BRD: CRM-LC-018 — Re-run duplicate detection when mobile or email changes.
     *
     * @param array<string, mixed> $data
     */
    public function update(Lead $lead, array $data): Lead
    {
        $updated = $this->repository->update($lead, $data);

        // BRD: CRM-LC-018 — Any change to contact details may reveal a new duplicate
        if (array_key_exists('mobile', $data) || array_key_exists('email', $data)) {
            DetectLeadDuplicatesJob::dispatch($lead->uuid, $lead->institution_id);
        }

        return $updated;
    }

    public function delete(Lead $lead): void
    {
        // BRD: Hard-delete is prohibited for lead records — soft delete only
        $this->repository->softDelete($lead);
    }
}
