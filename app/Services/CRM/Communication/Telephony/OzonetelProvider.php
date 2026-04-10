<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Telephony;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-016 — Ozonetel telephony provider adapter
final class OzonetelProvider implements TelephonyProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $userId,
    ) {}

    /**
     * @param array<string, mixed> $options
     * @return array{success: bool, call_id: ?string, error?: string}
     */
    public function initiateCall(string $from, string $to, array $options = []): array
    {
        $response = Http::get('https://in1-ccaas-api.ozonetel.com/CAServices/PhoneManualDail.php', [
            'apikey'   => $this->apiKey,
            'userid'   => $this->userId,
            'callerid' => $from,
            'phone'    => $to,
        ]);

        $data = $response->json();

        if (($data['status'] ?? '') === 'success') {
            return ['success' => true, 'call_id' => $data['call_id'] ?? null];
        }

        return ['success' => false, 'call_id' => null, 'error' => $data['msg'] ?? 'Unknown'];
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
            'call_id'       => $payload['ucid'] ?? $payload['call_id'] ?? '',
            'status'        => strtoupper($payload['disposition'] ?? $payload['status'] ?? 'UNKNOWN'),
            'from'          => $payload['callerid'] ?? '',
            'to'            => $payload['phone'] ?? '',
            'duration'      => (int) ($payload['duration'] ?? 0),
            'recording_url' => $payload['recording_file'] ?? null,
        ];
    }
}
