<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\WebFormSubmittedEvent;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-MA-002 — Trigger evaluation for form_submitted workflows
final class EvaluateAutomationOnWebFormSubmitted implements ShouldQueue
{
    public string $queue = 'crm-automation';

    public function handle(WebFormSubmittedEvent $event): void
    {
        EvaluateAutomationTriggerJob::dispatch(
            institutionId: (int) $event->lead->institution_id,
            leadId: (int) $event->lead->id,
            triggerType: 'form_submitted',
            context: [
                'form_uuid' => $event->form->uuid,
                'lead_uuid' => $event->lead->uuid,
            ],
        );
    }
}
