<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\LeadStatusChangedEvent;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-MA-002 — Trigger evaluation for status_changed workflows
final class EvaluateAutomationOnLeadStatusChanged implements ShouldQueue
{
    public string $queue = 'crm-automation';

    public function handle(LeadStatusChangedEvent $event): void
    {
        EvaluateAutomationTriggerJob::dispatch(
            institutionId: (int) $event->lead->institution_id,
            leadId: (int) $event->lead->id,
            triggerType: 'status_changed',
            context: [
                'previous_status' => $event->previousStatus->value,
                'new_status' => $event->newStatus->value,
            ],
        );
    }
}
