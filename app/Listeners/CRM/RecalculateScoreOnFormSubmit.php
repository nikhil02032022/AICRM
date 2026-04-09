<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\WebFormSubmittedEvent;
use App\Jobs\CRM\RecalculateLeadScoreJob;

// BRD: CRM-LQ-004 — Recalculate score on every qualifying activity; web form submission is one such activity
final class RecalculateScoreOnFormSubmit
{
    public function handle(WebFormSubmittedEvent $event): void
    {
        // The event carries the Lead model directly — dispatch recalculation job
        // BRD: CRM-CR-002 — No PII logged; pass only UUID
        RecalculateLeadScoreJob::dispatch((string) $event->lead->uuid);
    }
}
