<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-003, CRM-LC-004, CRM-LC-008
// Fired after a single lead is successfully imported from any digital channel webhook
final class DigitalLeadImportedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
    ) {}
}
