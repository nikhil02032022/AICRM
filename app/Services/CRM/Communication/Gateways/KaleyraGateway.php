<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Gateways;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-007 — Kaleyra SMS gateway adapter
final class KaleyraGateway implements SmsGatewayInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $sid,
        private readonly string $senderId,
    ) {}

    /** @return array{success: bool, message_id: ?string, error?: string} */
    public function send(string $to, string $message, string $senderId): array
    {
        $response = Http::withHeaders(['api-key' => $this->apiKey])
            ->post("https://api.kaleyra.io/v1/{$this->sid}/messages", [
                'to'      => $to,
                'type'    => 'SMS',
                'body'    => $message,
                'from'    => $senderId ?: $this->senderId,
            ]);

        $data = $response->json();

        if ($response->successful() && isset($data['data']['id'])) {
            return [
                'success'    => true,
                'message_id' => $data['data']['id'],
            ];
        }

        return [
            'success'    => false,
            'message_id' => null,
            'error'      => $data['message'] ?? 'Unknown error',
        ];
    }

    /** @return array{status: string, delivered_at?: string} */
    public function getDeliveryStatus(string $messageId): array
    {
        return ['status' => 'UNKNOWN'];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{message_id: string, status: string, delivered_at?: string}
     */
    public function parseDeliveryReceipt(array $payload): array
    {
        return [
            'message_id' => (string) ($payload['id'] ?? ''),
            'status'     => strtoupper((string) ($payload['status'] ?? 'UNKNOWN')),
        ];
    }
}
