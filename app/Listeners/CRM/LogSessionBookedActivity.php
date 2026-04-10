<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Events\CRM\CounsellingSessionBookedEvent;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-EC-015 — Log session_booked activity to the timeline
final class LogSessionBookedActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(CounsellingSessionBookedEvent $event): void
    {
        $session = $event->session;
        $lead = $session->lead;

        $this->activityRepository->createForSubject(new CreateActivityDTO(
            type: ActivityType::NOTE,
            subjectType: Lead::class,
            subjectId: $lead->getKey(),
            institutionId: $lead->institution_id,
            body: 'Counselling session scheduled on '.$session->scheduled_at?->format('d M Y, g:i A').'.',
            channel: null,
            direction: 'internal',
            metadata: [
                'session_type' => $session->session_type->value,
                'mode' => $session->mode,
                'session_uuid' => $session->getKey(),
            ],
            performedById: $session->counsellor_id,
        ));
    }
}
