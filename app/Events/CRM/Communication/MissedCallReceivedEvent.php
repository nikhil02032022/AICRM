<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-020 — Missed call received event
final class MissedCallReceivedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?Lead $lead,
        public readonly string $callerNumber,
        public readonly int $institutionId,
    ) {}
}
