<?php

declare(strict_types=1);

namespace App\Notifications\CRM\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// NFR-AV-002 — Email alert when failed_jobs count exceeds monitoring threshold.
final class FailedJobAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $failedCount,
        private readonly ?string $oldestFailedAt,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Action Required] {$this->failedCount} Failed Jobs in A2A-CRM Queue")
            ->greeting('Queue Alert')
            ->line("There are currently **{$this->failedCount} failed jobs** in the A2A-CRM queue.")
            ->when($this->oldestFailedAt, fn ($mail) => $mail->line("Oldest failure: {$this->oldestFailedAt}"))
            ->line('Please review and retry or discard failed jobs in the Horizon dashboard.')
            ->action('Open Horizon Dashboard', url('/horizon'))
            ->line('This alert fires automatically when the failed job count exceeds the configured threshold.');
    }
}
