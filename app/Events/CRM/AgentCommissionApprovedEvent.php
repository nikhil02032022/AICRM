<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AgentCommission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AG-006 — Fired when an agent commission is approved for payout
final class AgentCommissionApprovedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AgentCommission $commission
    ) {}
}
