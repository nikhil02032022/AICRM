<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Models\CRM\Payments\PaymentLink;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-FM-004 — Multi-channel notification carrying a payment link.
class PaymentLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PaymentLink $link,
        public readonly string $url,
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return match ($this->link->channel) {
            PaymentChannel::EMAIL => ['mail'],
            PaymentChannel::SMS, PaymentChannel::WHATSAPP => ['database'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Complete your payment')
            ->line('Please complete your payment using the secure link below.')
            ->action('Pay Now', $this->url)
            ->line('This link expires on '.optional($this->link->expires_at)->toDateTimeString().'.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'link_uuid' => $this->link->uuid,
            'channel'   => $this->link->channel?->value,
            'url'       => $this->url,
        ];
    }
}
