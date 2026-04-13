<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-MA-002 — Trigger evaluation for lead_score_changed workflows
final class EvaluateAutomationOnLeadTemperatureChanged implements ShouldQueue
{
    public string $queue = 'crm-automation';

    public function handle(LeadTemperatureChangedEvent $event): void
    {
        EvaluateAutomationTriggerJob::dispatch(
            institutionId: (int) $event->lead->institution_id,
            leadId: (int) $event->lead->id,
            triggerType: 'lead_score_changed',
            context: [
                'old_temperature' => $event->oldTemperature->value,
                'new_temperature' => $event->newTemperature->value,
            ],
        );
    }
}
