<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\LeadTemperature;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Jobs\CRM\QueueNurtureSequenceJob;
use App\Jobs\CRM\SendHotLeadAlertJob;

// BRD: CRM-LQ-006 — Score thresholds trigger automated workflow actions
final class TriggerScoringWorkflowListener
{
    public function handle(LeadTemperatureChangedEvent $event): void
    {
        match ($event->newTemperature) {
            // HOT: Immediate counsellor alert (DB notification + email)
            LeadTemperature::HOT => SendHotLeadAlertJob::dispatch($event->lead->uuid),

            // COLD downgrade: queue for nurture drip sequence (Group F Communication Engine)
            LeadTemperature::COLD => $this->maybeQueueNurture($event),

            // WARM, LOST, CONVERTED: no automated action at this stage
            default => null,
        };
    }

    private function maybeQueueNurture(LeadTemperatureChangedEvent $event): void
    {
        // Only trigger nurture if the lead was previously warmer (downgrade, not initial cold)
        $wasWarmer = in_array(
            $event->oldTemperature,
            [LeadTemperature::HOT, LeadTemperature::WARM],
            strict: true,
        );

        if ($wasWarmer) {
            QueueNurtureSequenceJob::dispatch($event->lead->uuid);
        }
    }
}
