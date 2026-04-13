<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Communication;

use App\Enums\CRM\CallStatus;
use App\Enums\CRM\TelephonyProvider;
use App\Events\CRM\Communication\CallCompletedEvent;
use App\Models\CRM\CallLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-016 — Process telephony provider webhook event
final class ProcessTelephonyWebhookJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 10;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly array $payload,
        public readonly string $provider,
    ) {
        $this->queue = 'crm-comms-voice';
    }

    public function uniqueId(): string
    {
        $callId = $this->payload['CallSid'] ?? $this->payload['call_id'] ?? $this->payload['ucid'] ?? '';

        return "telephony_webhook:{$this->provider}:{$callId}";
    }

    public function handle(): void
    {
        $callId = $this->payload['CallSid'] ?? $this->payload['call_id'] ?? $this->payload['ucid'] ?? '';

        if (empty($callId)) {
            return;
        }

        $callLog = CallLog::withoutGlobalScopes()
            ->where('provider_call_id', $callId)
            ->first();

        if ($callLog === null) {
            return;
        }

        $statusRaw = strtoupper($this->payload['Status'] ?? $this->payload['status'] ?? $this->payload['disposition'] ?? '');
        $status    = match ($statusRaw) {
            'COMPLETED', 'ANSWERED' => CallStatus::COMPLETED,
            'FAILED', 'BUSY'        => CallStatus::FAILED,
            'NO-ANSWER', 'NOANSWER' => CallStatus::NO_ANSWER,
            'RINGING'               => CallStatus::RINGING,
            'IN-PROGRESS'           => CallStatus::IN_PROGRESS,
            default                 => $callLog->status,
        };

        $recordingUrl = $this->payload['recording_url']
            ?? $this->payload['recording_file']
            ?? $this->payload['RecordingUrl']
            ?? null;

        $updates = [
            'status'           => $status,
            'duration_seconds' => (int) ($this->payload['Duration'] ?? $this->payload['duration'] ?? 0),
            'ended_at'         => now(),
        ];

        // BRD: CRM-TC-008 + DPDP — attach recording only when explicit call consent exists.
        if ($callLog->call_consent_given && is_string($recordingUrl) && $recordingUrl !== '') {
            $updates['recording_url'] = $recordingUrl;
        }

        $callLog->update($updates);

        if ($status->isTerminal()) {
            event(new CallCompletedEvent($callLog->lead, $callLog));
        }
    }
}
