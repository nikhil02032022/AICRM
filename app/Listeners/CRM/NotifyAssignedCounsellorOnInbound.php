<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\Communication\WhatsAppMessageReceivedEvent;
use App\Jobs\CRM\Communication\NotifyInboundMessageJob;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-CC-023 — Notify assigned counsellor on new inbound WhatsApp
final class NotifyAssignedCounsellorOnInbound implements ShouldQueue
{
    public string $queue = 'crm-notifications';

    public function handle(WhatsAppMessageReceivedEvent $event): void
    {
        $message      = $event->message;
        $conversation = $message->conversation()->first();

        if ($conversation === null) {
            return;
        }

        NotifyInboundMessageJob::dispatch('whatsapp', $conversation->id)
            ->onQueue('crm-notifications');
    }
}
