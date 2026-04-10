<?php

declare(strict_types=1);

namespace App\Notifications\CRM;

use App\Mail\CRM\HotLeadAlertMail;
use App\Models\CRM\Lead;
use Illuminate\Notifications\Notification;

// BRD: CRM-LQ-006 — Hot lead alert delivered via in-app (database) + email channels
final class HotLeadAlertNotification extends Notification
{
    public function __construct(
        public readonly Lead $lead,
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * In-app bell notification data.
     * BRD: CRM-CR-002 — Only routing data in notification; no raw PII fields.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'hot_lead_alert',
            'lead_uuid' => $this->lead->uuid,
            'lead_name' => $this->lead->fullName(),  // Counsellors need name for context
            'lead_score' => $this->lead->lead_score,
            'temperature' => $this->lead->temperature?->value,
            'source' => $this->lead->source?->value,
            'lead_url' => route('crm.leads.show', $this->lead->uuid),
            'message' => "Lead {$this->lead->fullName()} has reached HOT status (score: {$this->lead->lead_score}/100).",
        ];
    }

    public function toMail(object $notifiable): HotLeadAlertMail
    {
        return new HotLeadAlertMail($this->lead, $notifiable);
    }
}
