<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Documents;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Models\CRM\Documents\ApplicationDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-DM-005
class DocumentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ApplicationDocument $document,
        public readonly PaymentChannel $channel,
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return match ($this->channel) {
            PaymentChannel::EMAIL => ['mail'],
            PaymentChannel::SMS, PaymentChannel::WHATSAPP => ['database'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = optional($this->document->item)->label ?? 'document';

        return (new MailMessage)
            ->subject('Document pending')
            ->line("Your {$label} is still pending submission or review.")
            ->line('Please log in to your portal and complete the upload.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'document_uuid' => $this->document->uuid,
            'channel'       => $this->channel->value,
            'item_code'     => optional($this->document->item)->code,
        ];
    }
}
