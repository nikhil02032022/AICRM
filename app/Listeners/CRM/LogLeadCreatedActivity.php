<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Events\CRM\LeadCreatedEvent;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-EC-004 — Record a SYSTEM activity entry when a new lead is created
final class LogLeadCreatedActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(LeadCreatedEvent $event): void
    {
        $lead = $event->lead;

        $this->activityRepository->createSystemEntry(
            subjectType: Lead::class,
            subjectId: $lead->id,
            institutionId: $lead->institution_id,
            type: ActivityType::SYSTEM,
            body: 'Lead created.',
            metadata: ['source' => $lead->source?->value],
        );
    }
}
