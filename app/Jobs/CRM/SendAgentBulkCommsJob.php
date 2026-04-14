<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\AgentCommsLog;
use App\Services\CRM\Agent\AgentCommsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AG-008 — Async bulk communication delivery to agent network
final class SendAgentBulkCommsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        private readonly int $logId
    ) {}

    public function handle(AgentCommsService $service): void
    {
        $log = AgentCommsLog::withoutGlobalScopes()->findOrFail($this->logId);

        $recipientIds = $log->recipient_agent_ids ?? [];

        // BRD: CRM-AG-008 — Integration stub: replace with real email/WhatsApp/SMS dispatch
        // Actual implementation would:
        // 1. Look up each agent user's contact details (NOT stored in log — DPDP)
        // 2. Dispatch via the appropriate channel service (CommunicationService)
        // 3. Respect opt-out flags on each agent user record

        $delivered = count($recipientIds);
        $failed    = 0;

        $service->recordDelivery($log, $delivered, $failed);
    }

    public function failed(\Throwable $exception): void
    {
        $log = AgentCommsLog::withoutGlobalScopes()->find($this->logId);

        if ($log !== null) {
            $log->update(['status' => 'failed']);
        }
    }
}
