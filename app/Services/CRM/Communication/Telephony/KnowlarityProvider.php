<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Telephony;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-016 — Knowlarity telephony provider adapter
final class KnowlarityProvider implements TelephonyProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $accessToken,
        private readonly string $callerId,
    ) {}

    /**
     * @param array<string, mixed> $options
     * @return array{success: bool, call_id: ?string, error?: string}
     */
    public function initiateCall(string $from, string $to, array $options = []): array
    {
        $response = Http::withHeaders([
            'x-api-key'     => $this->apiKey,
            'Authorization' => 'Bearer '.$this->accessToken,
        ])->post('https://kpi.knowlarity.com/Basic/v1/account/call/makecall', [
            'caller_id'    => $this->callerId,
            'agent_number' => $from,
            'customer_number' => $to,
        ]);

        $data = $response->json();

        if ($response->successful() && isset($data['response']['call_id'])) {
            return ['success' => true, 'call_id' => $data['response']['call_id']];
        }

        return ['success' => false, 'call_id' => null, 'error' => $data['message'] ?? 'Unknown'];
    }

    /** @return array{duration: int, status: string, answered_at: ?string, ended_at: ?string} */
    public function getCallDetails(string $callId): array
    {
        return ['duration' => 0, 'status' => 'UNKNOWN', 'answered_at' => null, 'ended_at' => null];
    }

    public function getRecordingUrl(string $callId): ?string
    {
        return null;
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{call_id: string, status: string, from: string, to: string, duration?: int, recording_url?: string}
     */
    public function parseWebhookEvent(array $payload): array
    {
        return [
            'call_id'       => $payload['call_id'] ?? '',
            'status'        => strtoupper($payload['status'] ?? 'UNKNOWN'),
            'from'          => $payload['agent_number'] ?? '',
            'to'            => $payload['customer_number'] ?? '',
            'duration'      => (int) ($payload['duration'] ?? 0),
            'recording_url' => $payload['recording_url'] ?? null,
        ];
    }
}
