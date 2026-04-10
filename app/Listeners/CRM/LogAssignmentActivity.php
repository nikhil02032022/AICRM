<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Events\CRM\LeadAssignedEvent;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-EC-004 — Record an ASSIGNMENT activity entry on the lead timeline
final class LogAssignmentActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(LeadAssignedEvent $event): void
    {
        $lead = $event->lead;

        $body = $event->previousCounsellor
            ? sprintf(
                'Reassigned from %s to %s.',
                $event->previousCounsellor->name,
                $event->newCounsellor?->name ?? '(unassigned)',
            )
            : sprintf(
                'Assigned to %s.',
                $event->newCounsellor?->name ?? '(unassigned)',
            );

        $this->activityRepository->createSystemEntry(
            subjectType: Lead::class,
            subjectId: $lead->id,
            institutionId: $lead->institution_id,
            type: ActivityType::ASSIGNMENT,
            body: $body,
            metadata: [
                'new_counsellor_id' => $event->newCounsellor?->id,
                'prev_counsellor_id' => $event->previousCounsellor?->id,
            ],
        );
    }
}
