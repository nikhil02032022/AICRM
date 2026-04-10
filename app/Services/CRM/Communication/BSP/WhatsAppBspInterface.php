<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\BSP;

// BRD: CRM-CC-010 — Strategy interface for all WhatsApp BSP adapters
interface WhatsAppBspInterface
{
    /**
     * Send a pre-approved Meta message template.
     *
     * @param array<string, mixed> $params
     * @return array{success: bool, message_id: ?string, error?: string}
     */
    public function sendTemplate(string $to, string $templateName, array $params): array;

    /**
     * Send a free-form session message (within 24h conversation window).
     *
     * @return array{success: bool, message_id: ?string, error?: string}
     */
    public function sendMessage(string $to, string $message): array;

    /**
     * Mark a message or conversation as read.
     */
    public function markConversationRead(string $messageId): void;

    /**
     * Verify the inbound webhook signature from Meta/BSP.
     */
    public function verifySignature(string $payload, string $signature): bool;

    /**
     * Parse inbound BSP webhook payload into normalised message data.
     *
     * @param array<string, mixed> $payload
     * @return array<int, array{from: string, body: string, message_id: string, type: string, timestamp: string}>
     */
    public function parseInboundMessages(array $payload): array;
}
