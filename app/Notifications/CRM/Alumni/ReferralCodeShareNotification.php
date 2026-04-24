<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Alumni;

use App\Models\CRM\Alumni\AlumniReferralCode;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AL-002 — Sends referral code to alumni via email with sharing instructions
final class ReferralCodeShareNotification extends Notification
{
    use SerializesModels;

    public function __construct(
        private readonly AlumniReferralCode $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $campaign  = $this->code->campaign;
        $refLink   = url('/') . '?ref=' . $this->code->code;
        $rewardLabel = $campaign?->reward_type?->label() ?? 'reward';

        return (new MailMessage)
            ->subject('Your Alumni Referral Code — ' . ($campaign?->name ?? 'Referral Campaign'))
            ->greeting('Hello!')
            ->line('You have an active referral code for the campaign: **' . ($campaign?->name ?? 'Alumni Referral') . '**')
            ->line('**Your referral code: `' . $this->code->code . '`**')
            ->line('Share this link with prospective students so their applications are automatically credited to you:')
            ->action('Copy Referral Link', $refLink)
            ->line('When a referred student enrols, you will earn a ' . $rewardLabel . '.')
            ->line('Thank you for being an ambassador for your institution!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'               => 'referral_code_share',
            'code'               => $this->code->code,
            'campaign_id'        => $this->code->campaign_id,
            'alumni_pipeline_id' => $this->code->alumni_id,
            'campaign_name'      => $this->code->campaign?->name,
            'message'            => 'Your referral code ' . $this->code->code . ' is ready to share.',
        ];
    }
}
