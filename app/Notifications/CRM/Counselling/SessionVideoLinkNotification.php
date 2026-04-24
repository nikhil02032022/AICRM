<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Counselling;

use App\Models\CRM\CounsellingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-EC-018 — Sends the video meeting join link to the applicant via email
// DPDP: meeting_link is a functional URL, not PII; session details are minimal
final class SessionVideoLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly CounsellingSession $session,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $provider = $this->session->meeting_provider?->label() ?? 'Video';
        $time     = $this->session->scheduled_at?->format('d M Y, g:i A') ?? '';
        $counsellor = $this->session->counsellor?->name ?? 'your counsellor';

        return (new MailMessage)
            ->subject("Your {$provider} Meeting Link — Counselling Session")
            ->greeting('Hello!')
            ->line("Your counselling session with {$counsellor} is scheduled for {$time}.")
            ->line("Use the link below to join your {$provider} video session at the scheduled time.")
            ->action("Join {$provider} Session", $this->session->meeting_link ?? '#')
            ->line('Please join 2–3 minutes before your scheduled time.')
            ->line('If you have any questions, contact your institution.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'session_id' => $this->session->getKey(),
            'meeting_link' => $this->session->meeting_link,
            'meeting_provider' => $this->session->meeting_provider?->value,
            'scheduled_at' => $this->session->scheduled_at?->toIso8601String(),
            'message' => 'Your video meeting link is ready.',
        ];
    }
}
