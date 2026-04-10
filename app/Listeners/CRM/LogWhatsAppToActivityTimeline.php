<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Events\CRM\Communication\WhatsAppMessageReceivedEvent;
use App\Models\CRM\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-CC-022 — Log WhatsApp messages to the lead activity timeline
final class LogWhatsAppToActivityTimeline implements ShouldQueue
{
    public string $queue = 'crm-notifications';

    public function handle(WhatsAppMessageReceivedEvent $event): void
    {
        $message      = $event->message;
        $conversation = $message->conversation()->with('lead')->first();

        if ($conversation?->lead_id === null) {
            return;
        }

        Activity::create([
            'institution_id' => $message->institution_id,
            'lead_id'        => $conversation->lead_id,
            'type'           => ActivityType::WHATSAPP_SENT, // covers inbound too (display-side differentiated by metadata)
            'performed_by'   => null,
            'metadata'       => [
                'direction'  => $message->direction->value,
                'message_id' => $message->uuid,
                'type'       => $message->message_type->value,
            ],
        ]);
    }
}
