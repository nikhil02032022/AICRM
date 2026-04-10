<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Events\CRM\Communication\EmailSentEvent;
use App\Models\CRM\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-CC-022 — Log email sent to lead activity timeline
final class LogEmailSentToActivity implements ShouldQueue
{
    public string $queue = 'crm-notifications';

    public function handle(EmailSentEvent $event): void
    {
        Activity::create([
            'institution_id' => $event->lead->institution_id,
            'lead_id'        => $event->lead->id,
            'type'           => ActivityType::EMAIL_SENT,
            'performed_by'   => null, // system/queue context
            'metadata'       => [
                'log_uuid' => $event->log->uuid,
                'subject'  => $event->log->subject,
                'status'   => $event->log->status,
            ],
        ]);
    }
}
