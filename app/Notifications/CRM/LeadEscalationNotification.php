<?php

declare(strict_types=1);

namespace App\Notifications\CRM;

use App\Models\CRM\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-EC-009 — In-app + email alert sent when a lead exceeds the escalation threshold
final class LeadEscalationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Lead $lead,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action Required: Lead Needs Attention')
            ->line('A lead has not been contacted within the configured escalation window.')
            // BRD: CRM-CR-002 — No PII in mail; only UUID and status included
            ->line('Lead ID: '.$this->lead->uuid)
            ->line('Current Status: '.$this->lead->status->label())
            ->action('View Lead', url('/crm/leads/'.$this->lead->uuid))
            ->line('Please assign or contact this lead at the earliest.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'lead_uuid' => $this->lead->uuid,
            'status' => $this->lead->status->value,
            'message' => 'Lead requires attention — escalation threshold exceeded.',
        ];
    }
}
