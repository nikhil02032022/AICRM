<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Gateways;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-007 — Textlocal SMS gateway adapter
final class TextlocalGateway implements SmsGatewayInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId,
    ) {}

    /** @return array{success: bool, message_id: ?string, error?: string} */
    public function send(string $to, string $message, string $senderId): array
    {
        $response = Http::asForm()->post('https://api.textlocal.in/send/', [
            'apikey'  => $this->apiKey,
            'sender'  => $senderId ?: $this->senderId,
            'numbers' => $to,
            'message' => $message,
        ]);

        $data = $response->json();

        if (($data['status'] ?? '') === 'success') {
            return [
                'success'    => true,
                'message_id' => (string) ($data['messages'][0]['id'] ?? ''),
            ];
        }

        return [
            'success'    => false,
            'message_id' => null,
            'error'      => $data['errors'][0]['message'] ?? 'Unknown error',
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
