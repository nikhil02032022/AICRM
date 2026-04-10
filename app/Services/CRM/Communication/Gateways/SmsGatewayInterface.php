<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication\Gateways;

// BRD: CRM-CC-007 — Strategy interface for all SMS gateway adapters
interface SmsGatewayInterface
{
    /**
     * Send a single SMS message.
     *
     * @return array{success: bool, message_id: ?string, error?: string}
     */
    public function send(string $to, string $message, string $senderId): array;

    /**
     * Check delivery status for a message.
     *
     * @return array{status: string, delivered_at?: string}
     */
    public function getDeliveryStatus(string $messageId): array;

    /**
     * Parse delivery receipt webhook payload into a normalised structure.
     *
     * @param array<string, mixed> $payload
     * @return array{message_id: string, status: string, delivered_at?: string}
     */
    public function parseDeliveryReceipt(array $payload): array;
}
