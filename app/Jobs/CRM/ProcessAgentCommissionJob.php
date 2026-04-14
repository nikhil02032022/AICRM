<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\AgentCommission;
use App\Services\CRM\Agent\AgentCommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AG-006 — Async agent commission calculation and amount verification
final class ProcessAgentCommissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    public function __construct(
        private readonly int $commissionId
    ) {}

    public function handle(AgentCommissionService $service): void
    {
        $commission = AgentCommission::withoutGlobalScopes()->findOrFail($this->commissionId);

        // BRD: CRM-AG-006 — Integration stub: replace with real A2A Fee module verification
        // Actual implementation would verify commission amount against the fee paid in A2A ERP.
        // If percentage-based, confirm the base amount against ERP fee records.
        // Commission remains PENDING after this job — finance approves separately.
        // This job only validates/adjusts the amount if needed.

        if ($commission->commission_type === 'percentage' && $commission->base_amount !== null) {
            $calculated = ($commission->base_amount * ($commission->percentage_rate ?? 0)) / 100;
            $service->findByUuid($commission->uuid)?->update(['commission_amount' => round($calculated, 2)]);
        }
    }
}
