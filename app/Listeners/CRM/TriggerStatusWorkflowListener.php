<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\LeadStatusChangedEvent;
use App\Services\CRM\Counselling\LeadStatusWorkflowService;

// BRD: CRM-EC-012 — Trigger workflow automation on every status transition
final class TriggerStatusWorkflowListener
{
    public function __construct(
        private readonly LeadStatusWorkflowService $workflowService,
    ) {}

    public function handle(LeadStatusChangedEvent $event): void
    {
        $this->workflowService->handleStatusChange($event->lead, $event->newStatus);
    }
}
