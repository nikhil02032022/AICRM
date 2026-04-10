<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-005 — Email unsubscribe event (DPDP)
final class EmailUnsubscribedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly string $reason,
    ) {}
}
