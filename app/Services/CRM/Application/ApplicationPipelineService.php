<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\Enums\CRM\ApplicationStatus;
use App\Events\CRM\ApplicationStatusChangedEvent;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationStatusHistory;
use App\Repositories\CRM\Application\ApplicationRepositoryInterface;
use Illuminate\Validation\ValidationException;

// BRD: CRM-AP-008, CRM-AP-009, CRM-AP-011 — Application pipeline state management and seat availability
final class ApplicationPipelineService
{
    public function __construct(
        private readonly ApplicationRepositoryInterface $repository,
    ) {}

    /**
     * Promote a submitted draft to the pipeline (create Application entity).
     * Called after ApplicationFormDraft submission.
     * BRD: CRM-AP-008
     */
    public function promoteFromDraft(\App\Models\CRM\ApplicationFormDraft $draft): Application
    {
        // Check if application already exists for this draft (idempotent)
        if ($draft->lead_uuid && $existingApp = $this->repository->findByLeadUuidOrFail($draft->lead_uuid)) {
            return $existingApp;
        }

        return $this->repository->create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'institution_id' => $draft->institution_id,
            'campus_id' => $draft->campus_id,
            'lead_uuid' => $draft->lead_uuid ?? throw new \LogicException('Draft must have lead_uuid for promotion'),
            'application_form_draft_uuid' => $draft->uuid,
            'status' => ApplicationStatus::UNDER_REVIEW,
            'stage_entered_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    /**
     * Transition an application to a new pipeline stage with validation and audit.
     * BRD: CRM-AP-009 — Full status transition audit history maintained
     */
    public function transition(
        Application $application,
        ApplicationStatus $newStatus,
        ?int $changedByUserId = null,
        ?string $reason = null,
    ): Application {
        $currentStatus = $application->status;

        // Validate transition is allowed
        if (! $application->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Cannot transition from {$currentStatus->label()} to {$newStatus->label()}",
                ],
            ]);
        }

        // Update application status
        $application = $this->repository->update($application, [
            'status' => $newStatus,
            'stage_entered_at' => now(),
        ]);

        // Record audit history
        ApplicationStatusHistory::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'institution_id' => $application->institution_id,
            'application_uuid' => $application->uuid,
            'from_status' => $currentStatus->value,
            'to_status' => $newStatus->value,
            'changed_by_user_id' => $changedByUserId,
            'reason' => $reason,
        ]);

        // Fire event for listeners (audit logging, notifications, etc.)
        ApplicationStatusChangedEvent::dispatch(
            $application,
            $currentStatus->value,
            $newStatus->value,
            $reason,
            $changedByUserId,
        );

        return $application;
    }

    /**
     * Check seat availability for a programme.
     * BRD: CRM-AP-011 — Seat availability visibility
     * Returns: total_seats, allocated_seats, available_seats
     */
    public function checkSeatAvailability(string $programmeUuid): array
    {
        // TODO: Fetch from CrmProgramme or ERP integration
        // For now, return stub that can be mocked in tests
        return [
            'programme_uuid' => $programmeUuid,
            'total_seats' => 100,
            'allocated_seats' => 45,
            'available_seats' => 55,
        ];
    }

    /**
     * Get application count by status (for funnel analytics).
     */
    public function countByStatus(int $institutionId, array $filters = []): array
    {
        $baseQuery = Application::where('institution_id', $institutionId);

        if (isset($filters['admission_cycle_uuid'])) {
            $baseQuery->where('admission_cycle_uuid', $filters['admission_cycle_uuid']);
        }

        $counts = [];
        foreach (ApplicationStatus::cases() as $status) {
            $counts[$status->value] = $baseQuery->where('status', $status)->count();
        }

        return $counts;
    }
}
