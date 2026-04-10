<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Enums\CRM\LeadStatus;
use App\Events\CRM\CounsellingSessionCompletedEvent;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;
use App\Services\CRM\Lead\LeadService;

// BRD: CRM-EC-015 — Log completion activity and advance lead status
final class LogSessionCompletedActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
        private readonly LeadService $leadService,
    ) {}

    public function handle(CounsellingSessionCompletedEvent $event): void
    {
        $session = $event->session;
        $lead = $session->lead;

        // Log to timeline
        $this->activityRepository->createForSubject(new CreateActivityDTO(
            type: ActivityType::NOTE,
            subjectType: Lead::class,
            subjectId: $lead->getKey(),
            institutionId: $lead->institution_id,
            body: 'Counselling session completed on '.$session->completed_at?->format('d M Y, g:i A').'.',
            channel: null,
            direction: 'internal',
            metadata: [
                'session_uuid' => $session->getKey(),
                'session_type' => $session->session_type->value,
            ],
            performedById: $session->counsellor_id,
        ));

        // BRD: CRM-EC-013 — Auto-advance lead to counselling_done if currently in session
        if (in_array($lead->status, [LeadStatus::COUNSELLING_SCHEDULED], true)) {
            $this->leadService->transitionStatus($lead, LeadStatus::COUNSELLING_DONE);
        }
    }
}
