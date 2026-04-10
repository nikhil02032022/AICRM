<?php

declare(strict_types=1);

namespace App\Notifications\CRM;

use App\Models\CRM\CallLog;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-CC-020 — Missed call notification
final class MissedCallNotification extends Notification
{
    public function __construct(
        public readonly CallLog $callLog,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'missed_call',
            'call_uuid'   => $this->callLog->uuid,
            'direction'   => $this->callLog->direction->value,
            'status'      => $this->callLog->status->value,
            'called_at'   => $this->callLog->called_at?->toISOString(),
        ];
    }
}
