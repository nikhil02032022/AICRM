<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\CallLog;
use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-018 — Call logged with disposition event
final class CallLoggedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?Lead $lead,
        public readonly CallLog $callLog,
    ) {}
}
