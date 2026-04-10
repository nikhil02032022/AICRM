<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\WhatsAppMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-014 — WhatsApp inbound message received event
final class WhatsAppMessageReceivedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WhatsAppMessage $message,
    ) {}
}
