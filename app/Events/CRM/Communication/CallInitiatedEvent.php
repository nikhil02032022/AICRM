<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\CallLog;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-017 — Click-to-call initiated event
final class CallInitiatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly CallLog $callLog,
        public readonly User $counsellor,
    ) {}
}
