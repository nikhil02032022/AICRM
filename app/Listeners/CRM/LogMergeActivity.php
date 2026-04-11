<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Events\CRM\LeadsMergedEvent;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-LC-019 — Log a MERGE activity entry on both primary and secondary leads
// The secondary entry is readable via withTrashed() — it persists in the audit history.
final class LogMergeActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(LeadsMergedEvent $event): void
    {
        $primary = $event->primaryLead;
        $secondary = $event->secondaryLead;

        // Activity on primary: "absorbed" another lead
        $this->activityRepository->createSystemEntry(
            subjectType: Lead::class,
            subjectId: $primary->id,
            institutionId: $primary->institution_id,
            type: ActivityType::MERGE,
            body: "Lead record merged in: '{$secondary->fullName()}'. "
                . "{$event->mergedActivityCount} activities and {$event->mergedSessionCount} sessions transferred.",
            metadata: [
                'merged_secondary_uuid' => $secondary->uuid,
                'initiated_by_user_id' => $event->initiatedById,
                'merged_activity_count' => $event->mergedActivityCount,
                'merged_session_count' => $event->mergedSessionCount,
            ],
        );

        // Activity on secondary (will be soft-deleted but readable for audit)
        $this->activityRepository->createSystemEntry(
            subjectType: Lead::class,
            subjectId: $secondary->id,
            institutionId: $secondary->institution_id,
            type: ActivityType::MERGE,
            body: "This record was merged into '{$primary->fullName()}' and is no longer active.",
            metadata: [
                'merged_into_uuid' => $primary->uuid,
                'initiated_by_user_id' => $event->initiatedById,
            ],
        );
    }
}
