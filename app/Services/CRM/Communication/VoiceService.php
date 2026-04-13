<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallDisposition;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\TelephonyProvider;
use App\Events\CRM\Communication\CallCompletedEvent;
use App\Events\CRM\Communication\CallInitiatedEvent;
use App\Events\CRM\Communication\CallLoggedEvent;
use App\Jobs\CRM\Communication\ProcessOutboundCallJob;
use App\Jobs\CRM\Communication\ProcessTelephonyWebhookJob;
use App\Models\CRM\CallLog;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Communication\Telephony\TelephonyProviderInterface;

// BRD: CRM-CC-016, CRM-CC-017, CRM-CC-018 — Voice/telephony service
final class VoiceService
{
    /** @var array<string, TelephonyProviderInterface> */
    private array $providers = [];

    public function registerProvider(TelephonyProvider $provider, TelephonyProviderInterface $adapter): void
    {
        $this->providers[$provider->value] = $adapter;
    }

    private function resolveProvider(TelephonyProvider $provider): TelephonyProviderInterface
    {
        if (! isset($this->providers[$provider->value])) {
            throw new \RuntimeException("Telephony provider [{$provider->value}] not configured.");
        }

        return $this->providers[$provider->value];
    }

    /**
     * BRD: CRM-CC-017 — Initiate click-to-call from lead record.
     */
    public function initiateClickToCall(Lead $lead, User $counsellor): CallLog
    {
        $callLog = CallLog::create([
            'institution_id'     => $lead->institution_id,
            'lead_id'            => $lead->id,
            'telephony_provider' => TelephonyProvider::EXOTEL, // resolved from institution config
            'direction'          => CallDirection::OUTBOUND,
            'from_number'        => $counsellor->phone ?? '',
            'to_number'          => $lead->mobile,
            'call_consent_given' => (bool) $lead->call_consent_given,
            'status'             => CallStatus::INITIATED,
            'initiated_by'       => $counsellor->id,
            'called_at'          => now(),
        ]);

        ProcessOutboundCallJob::dispatch($callLog->id)->onQueue('crm-comms-voice');

        event(new CallInitiatedEvent($lead, $callLog, $counsellor));

        return $callLog;
    }

    /**
     * BRD: CRM-CC-018 — Finalise call with outcome after call ends.
     *
     * @param array<string, mixed> $outcome
     */
    public function finaliseCallLog(CallLog $callLog, array $outcome): CallLog
    {
        $callLog->update([
            'status'            => CallStatus::COMPLETED,
            'duration_seconds'  => $outcome['duration_seconds'] ?? 0,
            'disposition'       => isset($outcome['disposition']) ? CallDisposition::from($outcome['disposition']) : null,
            'disposition_notes' => $outcome['notes'] ?? null,
            'ended_at'          => now(),
        ]);

        event(new CallCompletedEvent($callLog->lead, $callLog));
        event(new CallLoggedEvent($callLog->lead, $callLog));

        return $callLog->fresh();
    }

    /**
     * BRD: CRM-CC-018 — Attach recording URL (only if call_consent_given = true, DPDP).
     */
    public function attachRecording(CallLog $callLog, User $requester): CallLog
    {
        // BRD: CRM-CR-004 — Never retrieve recording without consent
        if (! $callLog->call_consent_given) {
            throw new \RuntimeException('Call recording consent not given. Recording cannot be attached.');
        }

        $provider = $this->resolveProvider($callLog->telephony_provider);
        $url      = $provider->getRecordingUrl($callLog->provider_call_id ?? '');

        if ($url !== null) {
            $callLog->update(['recording_url' => $url]);
        }

        return $callLog->fresh();
    }

    /**
     * BRD: CRM-CC-016 — Handle provider webhook event asynchronously.
     *
     * @param array<string, mixed> $payload
     */
    public function handleProviderEvent(array $payload, string $provider): void
    {
        ProcessTelephonyWebhookJob::dispatch($payload, $provider)->onQueue('crm-comms-voice');
    }
}
