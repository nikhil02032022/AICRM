<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\Communication\CallLoggedEvent;
use App\Models\User;
use App\Notifications\CRM\MissedCallNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-CC-020 — Notify counsellor on missed call
final class NotifyCounsellorOnMissedCall implements ShouldQueue
{
    public string $queue = 'crm-notifications';

    public function handle(CallLoggedEvent $event): void
    {
        if ($event->callLog->disposition !== null) {
            return; // Already logged with outcome
        }

        if ($event->lead?->assigned_counsellor_id === null) {
            return;
        }

        $counsellor = User::find($event->lead->assigned_counsellor_id);

        $counsellor?->notify(new MissedCallNotification($event->callLog));
    }
}
