<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Telephony;

// BRD: CRM-CC-016 — Strategy interface for telephony provider adapters
interface TelephonyProviderInterface
{
    /**
     * Initiate an outbound call between agent and lead.
     *
     * @param array<string, mixed> $options
     * @return array{success: bool, call_id: ?string, error?: string}
     */
    public function initiateCall(string $from, string $to, array $options = []): array;

    /**
     * Retrieve call details (duration, status) for a completed call.
     *
     * @return array{duration: int, status: string, answered_at: ?string, ended_at: ?string}
     */
    public function getCallDetails(string $callId): array;

    /**
     * Get the recording URL for a completed call (only if consent given).
     */
    public function getRecordingUrl(string $callId): ?string;

    /**
     * Verify webhook signature from this provider.
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool;

    /**
     * Parse provider webhook payload into normalised call event data.
     *
     * @param array<string, mixed> $payload
     * @return array{call_id: string, status: string, from: string, to: string, duration?: int, recording_url?: string}
     */
    public function parseWebhookEvent(array $payload): array;
}
