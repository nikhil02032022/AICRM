<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\BSP;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-010 — Gupshup WhatsApp BSP adapter
final class GupshupBsp implements WhatsAppBspInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $appName,
        private readonly string $sourcePhone,
        private readonly string $webhookSecret,
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return array{success: bool, message_id: ?string, error?: string}
     */
    public function sendTemplate(string $to, string $templateName, array $params): array
    {
        $response = Http::withHeaders(['apikey' => $this->apiKey])
            ->asForm()
            ->post('https://api.gupshup.io/sm/api/v1/template/msg', [
                'channel'    => 'whatsapp',
                'source'     => $this->sourcePhone,
                'destination'=> $to,
                'template'   => json_encode([
                    'id'     => $templateName,
                    'params' => array_values($params),
                ]),
                'src.name'   => $this->appName,
            ]);

        $data = $response->json();

        return $response->successful()
            ? ['success' => true, 'message_id' => $data['messageId'] ?? null]
            : ['success' => false, 'message_id' => null, 'error' => $data['message'] ?? 'Unknown'];
    }

    /** @return array{success: bool, message_id: ?string, error?: string} */
    public function sendMessage(string $to, string $message): array
    {
        $response = Http::withHeaders(['apikey' => $this->apiKey])
            ->asForm()
            ->post('https://api.gupshup.io/sm/api/v1/msg', [
                'channel'     => 'whatsapp',
                'source'      => $this->sourcePhone,
                'destination' => $to,
                'message'     => json_encode(['type' => 'text', 'text' => $message]),
                'src.name'    => $this->appName,
            ]);

        $data = $response->json();

        return $response->successful()
            ? ['success' => true, 'message_id' => $data['messageId'] ?? null]
            : ['success' => false, 'message_id' => null, 'error' => $data['message'] ?? 'Unknown'];
    }

    public function markConversationRead(string $messageId): void
    {
        // Gupshup handles read receipts internally
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{from: string, body: string, message_id: string, type: string, timestamp: string}>
     */
    public function parseInboundMessages(array $payload): array
    {
        if (isset($payload['payload']['source'])) {
            return [[
                'from'       => $payload['payload']['source'],
                'body'       => $payload['payload']['payload']['text'] ?? '',
                'message_id' => $payload['payload']['id'] ?? '',
                'type'       => strtoupper($payload['payload']['type'] ?? 'TEXT'),
                'timestamp'  => (string) ($payload['timestamp'] ?? ''),
            ]];
        }

        return [];
    }
}
