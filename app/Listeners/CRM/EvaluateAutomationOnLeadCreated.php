<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\LeadCreatedEvent;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-MA-002 — Trigger evaluation for lead_created workflows
final class EvaluateAutomationOnLeadCreated implements ShouldQueue
{
    public string $queue = 'crm-automation';

    public function handle(LeadCreatedEvent $event): void
    {
        EvaluateAutomationTriggerJob::dispatch(
            institutionId: (int) $event->lead->institution_id,
            leadId: (int) $event->lead->id,
            triggerType: 'lead_created',
            context: [
                'lead_uuid' => $event->lead->uuid,
                'source' => $event->lead->source?->value,
            ],
        );
    }
}
