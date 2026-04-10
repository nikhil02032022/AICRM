<?php

declare(strict_types=1);

namespace App\Notifications\CRM;

use App\Models\CRM\CallLog;
use Illuminate\Notifications\Notification;

// BRD: CRM-CC-023 — IVR lead created notification for fallback counsellor
final class IvrLeadCreatedNotification extends Notification
{
    public function __construct(
        public readonly \App\Models\CRM\Lead $lead,
        public readonly \App\Models\CRM\IvrConfig $ivrConfig,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('New IVR Lead — A2A CRM')
            ->line('A new lead was auto-created from an IVR inbound call.')
            ->action('View Lead', route('crm.leads.show', $this->lead->uuid));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'ivr_lead_created',
            'lead_uuid' => $this->lead->uuid,
        ];
    }
}
