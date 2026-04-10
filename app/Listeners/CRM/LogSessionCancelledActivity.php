<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Events\CRM\CounsellingSessionCancelledEvent;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-EC-015 — Log cancellation/no-show activity to timeline
final class LogSessionCancelledActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(CounsellingSessionCancelledEvent $event): void
    {
        $session = $event->session;
        $lead = $session->lead;

        $this->activityRepository->createForSubject(new CreateActivityDTO(
            type: ActivityType::NOTE,
            subjectType: Lead::class,
            subjectId: $lead->getKey(),
            institutionId: $lead->institution_id,
            body: 'Session '.$session->status->label().' — originally scheduled '.$session->scheduled_at?->format('d M Y, g:i A').'.',
            channel: null,
            direction: 'internal',
            metadata: [
                'session_uuid' => $session->getKey(),
                'status' => $session->status->value,
            ],
            performedById: $session->counsellor_id,
        ));
    }
}
