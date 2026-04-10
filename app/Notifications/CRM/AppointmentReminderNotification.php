<?php

declare(strict_types=1);

namespace App\Notifications\CRM;

use App\Models\CRM\CounsellingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-EC-017 — Appointment reminder notification (24h and 1h windows)
// DPDP: No PII in mail body — only session UUID and scheduled time
final class AppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly CounsellingSession $session,
        private readonly string $window, // '24h' | '1h'
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->window === '24h' ? '24 hours' : '1 hour';

        return (new MailMessage)
            ->subject("Reminder: Counselling Session in {$label}")
            ->line("Your counselling session is scheduled in {$label}.")
            ->line('Scheduled at: '.$this->session->scheduled_at?->format('d M Y, g:i A'))
            ->line('Mode: '.ucfirst($this->session->mode))
            ->action('View Details', url('/crm/leads/'.$this->session->lead?->uuid))
            ->line('Please ensure you are prepared for the session.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'session_uuid' => $this->session->getKey(),
            'scheduled_at' => $this->session->scheduled_at?->toIso8601String(),
            'window' => $this->window,
            'message' => "Counselling session reminder ({$this->window}).",
        ];
    }
}
