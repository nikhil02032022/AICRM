<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\DigitalLeadImportedEvent;
use App\Jobs\CRM\DetectLeadDuplicatesJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-LC-018 — Trigger duplicate detection whenever a lead is imported from a digital channel
// Runs async on the default queue — does not block the webhook ACK
final class TriggerDuplicateDetectionOnImport implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(DigitalLeadImportedEvent $event): void
    {
        DetectLeadDuplicatesJob::dispatch(
            $event->lead->uuid,
            $event->lead->institution_id,
        );
    }
}
