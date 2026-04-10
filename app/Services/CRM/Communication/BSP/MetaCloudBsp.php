<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\BSP;

use Illuminate\Support\Facades\Http;

// BRD: CRM-CC-010 — Meta Cloud API v17+ WhatsApp BSP adapter
final class MetaCloudBsp implements WhatsAppBspInterface
{
    public function __construct(
        private readonly string $phoneNumberId,
        private readonly string $accessToken,
        private readonly string $appSecret,
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return array{success: bool, message_id: ?string, error?: string}
     */
    public function sendTemplate(string $to, string $templateName, array $params): array
    {
        $components = [];

        if (! empty($params)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn ($val) => ['type' => 'text', 'text' => (string) $val], $params),
            ];
        }

        $response = Http::withToken($this->accessToken)
            ->post("https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template'          => [
                    'name'       => $templateName,
                    'language'   => ['code' => 'en'],
                    'components' => $components,
                ],
            ]);

        $data = $response->json();

        if ($response->successful() && isset($data['messages'][0]['id'])) {
            return ['success' => true, 'message_id' => $data['messages'][0]['id']];
        }

        return ['success' => false, 'message_id' => null, 'error' => $data['error']['message'] ?? 'Unknown'];
    }

    /** @return array{success: bool, message_id: ?string, error?: string} */
    public function sendMessage(string $to, string $message): array
    {
        $response = Http::withToken($this->accessToken)
            ->post("https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        $data = $response->json();

        if ($response->successful() && isset($data['messages'][0]['id'])) {
            return ['success' => true, 'message_id' => $data['messages'][0]['id']];
        }

        return ['success' => false, 'message_id' => null, 'error' => $data['error']['message'] ?? 'Unknown'];
    }

    public function markConversationRead(string $messageId): void
    {
        Http::withToken($this->accessToken)
            ->post("https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'status'            => 'read',
                'message_id'        => $messageId,
            ]);
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        $expected = 'sha256='.hash_hmac('sha256', $payload, $this->appSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{from: string, body: string, message_id: string, type: string, timestamp: string}>
     */
    public function parseInboundMessages(array $payload): array
    {
        $messages = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $msg) {
                    $messages[] = [
                        'from'       => $msg['from'],
                        'body'       => $msg['text']['body'] ?? '',
                        'message_id' => $msg['id'],
                        'type'       => strtoupper($msg['type'] ?? 'TEXT'),
                        'timestamp'  => $msg['timestamp'],
                    ];
                }
            }
        }

        return $messages;
    }
}
