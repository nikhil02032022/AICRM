<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\BSP;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-010 — Interakt WhatsApp BSP adapter
final class InteraktBsp implements WhatsAppBspInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $webhookSecret,
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return array{success: bool, message_id: ?string, error?: string}
     */
    public function sendTemplate(string $to, string $templateName, array $params): array
    {
        $response = Http::withHeaders(['Authorization' => 'Basic '.base64_encode($this->apiKey.':')])
            ->post('https://api.interakt.ai/v1/public/message/', [
                'countryCode'  => '+91',
                'phoneNumber'  => $to,
                'callbackData' => 'crm',
                'type'         => 'Template',
                'template'     => [
                    'name'             => $templateName,
                    'languageCode'     => 'en',
                    'bodyValues'       => array_values($params),
                ],
            ]);

        $data = $response->json();

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $data['id'] ?? null];
        }

        return ['success' => false, 'message_id' => null, 'error' => $data['message'] ?? 'Unknown'];
    }

    /** @return array{success: bool, message_id: ?string, error?: string} */
    public function sendMessage(string $to, string $message): array
    {
        $response = Http::withHeaders(['Authorization' => 'Basic '.base64_encode($this->apiKey.':')])
            ->post('https://api.interakt.ai/v1/public/message/', [
                'countryCode' => '+91',
                'phoneNumber' => $to,
                'callbackData'=> 'crm',
                'type'        => 'Text',
                'data'        => ['message' => $message],
            ]);

        $data = $response->json();

        return $response->successful()
            ? ['success' => true, 'message_id' => $data['id'] ?? null]
            : ['success' => false, 'message_id' => null, 'error' => $data['message'] ?? 'Unknown'];
    }

    public function markConversationRead(string $messageId): void
    {
        // Interakt handles read receipts automatically
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
        // Interakt webhook normalisation
        if (isset($payload['data']['message'])) {
            $msg = $payload['data']['message'];

            return [[
                'from'       => $payload['data']['customer']['phone_number'] ?? '',
                'body'       => $msg['text'] ?? '',
                'message_id' => $msg['id'] ?? '',
                'type'       => strtoupper($msg['type'] ?? 'TEXT'),
                'timestamp'  => $msg['timestamp'] ?? '',
            ]];
        }

        return [];
    }
}
