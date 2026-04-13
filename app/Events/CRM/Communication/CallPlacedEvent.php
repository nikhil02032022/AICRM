<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\CallLog;
use App\Models\CRM\DiallerLog;
use App\Models\CRM\DiallerSession;
use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-TC-001 — Event fired when dialler successfully places a queued call
final class CallPlacedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly CallLog $callLog,
        public readonly DiallerSession $diallerSession,
        public readonly DiallerLog $diallerLog,
    ) {}
}
