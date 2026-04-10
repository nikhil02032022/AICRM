<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Enums\CRM\LeadStatus;
use App\Events\CRM\LeadStatusChangedEvent;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-EC-014 — Every status transition is recorded in the activity timeline
// DPDP: body must not contain PII — uses status labels only, never name/mobile/email
final class LogStatusChangeActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(LeadStatusChangedEvent $event): void
    {
        $lead = $event->lead;
        $from = $event->previousStatus;
        $to = $event->newStatus;

        $body = sprintf(
            'Status changed: %s → %s.',
            $from->label(),
            $to->label(),
        );

        // Append lost reason to the body when available — no PII included
        if ($to === LeadStatus::LOST && $lead->lost_reason !== null) {
            $body .= ' Reason: '.$lead->lost_reason->label().'.';
        }

        $this->activityRepository->createSystemEntry(
            subjectType: Lead::class,
            subjectId: $lead->id,
            institutionId: $lead->institution_id,
            type: ActivityType::STATUS_CHANGE,
            body: $body,
            metadata: [
                'from' => $from->value,
                'to' => $to->value,
                'lost_reason' => $lead->lost_reason?->value,
            ],
        );
    }
}
