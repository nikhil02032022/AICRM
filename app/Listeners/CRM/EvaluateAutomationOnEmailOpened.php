<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\Communication\EmailOpenedEvent;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-MA-002 — Trigger evaluation for email_opened workflows
final class EvaluateAutomationOnEmailOpened implements ShouldQueue
{
    public string $queue = 'crm-automation';

    public function handle(EmailOpenedEvent $event): void
    {
        EvaluateAutomationTriggerJob::dispatch(
            institutionId: (int) $event->lead->institution_id,
            leadId: (int) $event->lead->id,
            triggerType: 'email_opened',
            context: [
                'communication_log_uuid' => $event->log->uuid,
            ],
        );
    }
}
