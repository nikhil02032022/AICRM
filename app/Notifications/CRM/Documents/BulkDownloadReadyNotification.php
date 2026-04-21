<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Documents;

use App\Models\CRM\Documents\DocumentBulkDownloadJob;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-DM-009
class BulkDownloadReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly DocumentBulkDownloadJob $job,
        public readonly string $url,
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your document bundle is ready')
            ->line("Bundle #{$this->job->uuid} is ready for download.")
            ->action('Download ZIP', $this->url)
            ->line('Link expires at '.optional($this->job->expires_at)->toDateTimeString().'.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'bulk_download_uuid' => $this->job->uuid,
            'url'                => $this->url,
            'expires_at'         => optional($this->job->expires_at)->toIso8601String(),
        ];
    }
}
