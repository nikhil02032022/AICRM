<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Gateways;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-007 — MSG91 SMS gateway adapter
final class Msg91Gateway implements SmsGatewayInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId,
    ) {}

    /** @return array{success: bool, message_id: ?string, error?: string} */
    public function send(string $to, string $message, string $senderId): array
    {
        $response = Http::withHeaders([
            'authkey'      => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.msg91.com/api/v5/flow/', [
            'sender'    => $senderId ?: $this->senderId,
            'short_url' => '0',
            'mobiles'   => $to,
            'message'   => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success'    => true,
                'message_id' => $data['request_id'] ?? null,
            ];
        }

        return [
            'success' => false,
            'message_id' => null,
            'error'   => $response->body(),
        ];
    }

    /** @return array{status: string, delivered_at?: string} */
    public function getDeliveryStatus(string $messageId): array
    {
        $response = Http::withHeaders(['authkey' => $this->apiKey])
            ->get("https://api.msg91.com/api/v5/report/", ['msgid' => $messageId]);

        $data = $response->json();

        return [
            'status'       => $data['data']['status'] ?? 'UNKNOWN',
            'delivered_at' => $data['data']['delivered_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{message_id: string, status: string, delivered_at?: string}
     */
    public function parseDeliveryReceipt(array $payload): array
    {
        return [
            'message_id'   => (string) ($payload['requestId'] ?? $payload['msgId'] ?? ''),
            'status'       => strtoupper((string) ($payload['status'] ?? 'UNKNOWN')),
            'delivered_at' => $payload['deliveredAt'] ?? null,
        ];
    }
}
