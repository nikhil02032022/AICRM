<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\LeadTemperature;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-MA-007 — Trigger re-engagement workflows for cold lead downgrades
final class EvaluateReEngagementOnLeadTemperatureChanged implements ShouldQueue
{
    public string $queue = 'crm-automation';

    public function handle(LeadTemperatureChangedEvent $event): void
    {
        if ($event->newTemperature !== LeadTemperature::COLD) {
            return;
        }

        EvaluateAutomationTriggerJob::dispatch(
            institutionId: (int) $event->lead->institution_id,
            leadId: (int) $event->lead->id,
            triggerType: 're_engagement',
            context: [
                'reason' => 'cold',
                'old_temperature' => $event->oldTemperature->value,
                'new_temperature' => $event->newTemperature->value,
            ],
        );
    }
}
