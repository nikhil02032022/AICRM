<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\Communication\EmailUnsubscribedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-CC-005 — Log unsubscribe to activity timeline
final class HandleLeadUnsubscribe implements ShouldQueue
{
    public string $queue = 'crm-notifications';

    public function handle(EmailUnsubscribedEvent $event): void
    {
        \App\Models\CRM\Activity::create([
            'institution_id' => $event->lead->institution_id,
            'lead_id'        => $event->lead->id,
            'type'           => \App\Enums\CRM\ActivityType::SYSTEM,
            'performed_by'   => null,
            'metadata'       => [
                'action' => 'email_unsubscribed',
                'reason' => $event->reason,
            ],
        ]);
    }
}
