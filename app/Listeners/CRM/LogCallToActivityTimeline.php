<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Events\CRM\Communication\CallLoggedEvent;
use App\Models\CRM\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-CC-022 — Log call to lead activity timeline
final class LogCallToActivityTimeline implements ShouldQueue
{
    public string $queue = 'crm-notifications';

    public function handle(CallLoggedEvent $event): void
    {
        if ($event->lead === null) {
            return;
        }

        Activity::create([
            'institution_id' => $event->callLog->institution_id,
            'lead_id'        => $event->lead->id,
            'type'           => ActivityType::CALL_LOGGED,
            'performed_by'   => $event->callLog->initiated_by,
            'metadata'       => [
                'call_uuid'   => $event->callLog->uuid,
                'direction'   => $event->callLog->direction->value,
                'duration'    => $event->callLog->duration_seconds,
                'disposition' => $event->callLog->disposition?->value,
                'status'      => $event->callLog->status->value,
            ],
        ]);
    }
}
