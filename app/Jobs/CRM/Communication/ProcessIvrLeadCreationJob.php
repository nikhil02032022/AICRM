<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Models\CRM\IvrConfig;
use App\Services\CRM\Communication\IvrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-010 — IVR inbound lead auto-creation job
final class ProcessIvrLeadCreationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 5;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly array $payload,
        public readonly int $ivrConfigId,
    ) {
        $this->queue = 'crm-comms-voice';
    }

    public function uniqueId(): string
    {
        $callId = $this->payload['CallSid'] ?? $this->payload['call_id'] ?? '';

        return "ivr_lead:{$callId}";
    }

    public function handle(IvrService $ivrService): void
    {
        $config = IvrConfig::withoutGlobalScopes()->find($this->ivrConfigId);

        if ($config === null) {
            return;
        }

        $ivrService->handleInboundIvrCall($this->payload, $config);
    }
}
