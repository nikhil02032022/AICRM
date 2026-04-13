<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\Communication\EmailLinkClickedEvent;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-MA-002 — Trigger evaluation for link_clicked workflows
final class EvaluateAutomationOnEmailLinkClicked implements ShouldQueue
{
    public string $queue = 'crm-automation';

    public function handle(EmailLinkClickedEvent $event): void
    {
        EvaluateAutomationTriggerJob::dispatch(
            institutionId: (int) $event->lead->institution_id,
            leadId: (int) $event->lead->id,
            triggerType: 'link_clicked',
            context: [
                'communication_log_uuid' => $event->log->uuid,
            ],
        );
    }
}
