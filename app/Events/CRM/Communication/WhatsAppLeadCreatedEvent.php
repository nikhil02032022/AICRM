<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\IvrConfig;
use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-007 — WhatsApp auto-created lead event
final class WhatsAppLeadCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
    ) {}
}
