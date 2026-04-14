<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AgentCommsLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AG-008 — Fired when a bulk agent communication is fully delivered
final class AgentBulkCommsSentEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AgentCommsLog $commsLog
    ) {}
}
