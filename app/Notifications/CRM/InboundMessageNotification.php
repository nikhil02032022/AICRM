<?php

declare(strict_types=1);

namespace App\Notifications\CRM;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-CC-023 — In-app + email notification for new inbound message
final class InboundMessageNotification extends Notification
{
    public function __construct(
        public readonly string $channel,
        public readonly int $entityId,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Inbound Message — A2A CRM')
            ->line("You have received a new inbound {$this->channel} message.")
            ->action('View Inbox', route('crm.inbox.index'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'channel'   => $this->channel,
            'entity_id' => $this->entityId,
            'type'      => 'inbound_message',
        ];
    }
}
