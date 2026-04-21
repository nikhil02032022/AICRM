<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Scholarships;

use App\Models\CRM\Scholarships\ScholarshipAward;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-FM-008 — Notify next approver in chain.
class ApprovalPendingNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ScholarshipAward $award) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Scholarship approval pending')
            ->line("Award #{$this->award->uuid} awaits your approval.")
            ->line("Amount: {$this->award->amount}");
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'award_uuid' => $this->award->uuid,
            'stage'      => $this->award->current_stage?->value,
            'amount'     => (float) $this->award->amount,
        ];
    }
}
