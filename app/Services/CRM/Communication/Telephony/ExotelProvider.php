<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Telephony;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-016 — Exotel telephony provider adapter
final class ExotelProvider implements TelephonyProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiToken,
        private readonly string $accountSid,
        private readonly string $subdomain = 'api.exotel.com',
    ) {}

    /**
     * @param array<string, mixed> $options
     * @return array{success: bool, call_id: ?string, error?: string}
     */
    public function initiateCall(string $from, string $to, array $options = []): array
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiToken)
            ->asForm()
            ->post("https://{$this->subdomain}/v1/Accounts/{$this->accountSid}/Calls/connect", [
                'From'       => $from,
                'To'         => $to,
                'CallerId'   => $options['caller_id'] ?? $from,
                'CallType'   => 'trans',
                'TimeLimit'  => $options['time_limit'] ?? 14400,
                'Record'     => $options['record'] ?? 'false',
            ]);

        $data = $response->json();

        if ($response->successful() && isset($data['Call']['Sid'])) {
            return ['success' => true, 'call_id' => $data['Call']['Sid']];
        }

        return ['success' => false, 'call_id' => null, 'error' => $data['RestException']['Message'] ?? 'Unknown'];
    }

    /** @return array{duration: int, status: string, answered_at: ?string, ended_at: ?string} */
    public function getCallDetails(string $callId): array
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiToken)
            ->get("https://{$this->subdomain}/v1/Accounts/{$this->accountSid}/Calls/{$callId}.json");

        $data = $response->json()['Call'] ?? [];

        return [
            'duration'    => (int) ($data['Duration'] ?? 0),
            'status'      => strtoupper($data['Status'] ?? 'UNKNOWN'),
            'answered_at' => $data['StartTime'] ?? null,
            'ended_at'    => $data['EndTime'] ?? null,
        ];
    }

    public function getRecordingUrl(string $callId): ?string
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiToken)
            ->get("https://{$this->subdomain}/v1/Accounts/{$this->accountSid}/Calls/{$callId}/Recordings.json");

        $recordings = $response->json()['Recordings'] ?? [];

        return $recordings[0]['Uri'] ?? null;
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        return hash_equals(hash_hmac('sha1', $payload, $secret), $signature);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{call_id: string, status: string, from: string, to: string, duration?: int, recording_url?: string}
     */
    public function parseWebhookEvent(array $payload): array
    {
        return [
            'call_id'       => $payload['CallSid'] ?? '',
            'status'        => strtoupper($payload['Status'] ?? 'UNKNOWN'),
            'from'          => $payload['From'] ?? '',
            'to'            => $payload['To'] ?? '',
            'duration'      => (int) ($payload['Duration'] ?? 0),
            'recording_url' => $payload['RecordingUrl'] ?? null,
        ];
    }
}
