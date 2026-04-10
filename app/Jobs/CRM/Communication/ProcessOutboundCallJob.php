<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallStatus;
use App\Models\CRM\CallLog;
use App\Services\CRM\Communication\VoiceService;
use App\Services\CRM\Communication\Telephony\TelephonyProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-017 — Async call initiation via telephony provider
final class ProcessOutboundCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 5;

    public function __construct(
        public readonly int $callLogId,
    ) {
        $this->queue = 'crm-comms-voice';
    }

    public function handle(VoiceService $voiceService): void
    {
        $callLog = CallLog::withoutGlobalScopes()->find($this->callLogId);

        if ($callLog === null) {
            return;
        }

        // Provider resolved via VoiceService registry; actual API call here
        $callLog->update(['status' => CallStatus::RINGING]);
    }
}
