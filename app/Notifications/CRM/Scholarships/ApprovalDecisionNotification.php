<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Scholarships;

use App\Models\CRM\Scholarships\ScholarshipAward;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-FM-008
class ApprovalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ScholarshipAward $award,
        public readonly string $decision,
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Scholarship {$this->decision}")
            ->line("Award #{$this->award->uuid} has been {$this->decision}.");
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'award_uuid' => $this->award->uuid,
            'decision'   => $this->decision,
            'status'     => $this->award->status?->value,
        ];
    }
}
