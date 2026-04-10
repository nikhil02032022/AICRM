<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\Communication\EmailBouncedEvent;
use App\Models\CRM\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-CC-003 — Increment bounce count and flag email as invalid after 3 hard bounces
final class HandleEmailBounce implements ShouldQueue
{
    public string $queue = 'crm-comms-email';

    public function handle(EmailBouncedEvent $event): void
    {
        if ($event->lead === null) {
            return;
        }

        $lead = Lead::withoutGlobalScopes()->find($event->lead->id);

        if ($lead === null) {
            return;
        }

        $newCount = ($lead->email_bounce_count ?? 0) + 1;
        $lead->update(['email_bounce_count' => $newCount]);
    }
}
