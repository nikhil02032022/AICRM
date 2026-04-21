<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Gateways;

use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\RefundRequest;
use App\Services\CRM\Payments\Gateways\DTO\GatewayOrder;
use App\Services\CRM\Payments\Gateways\DTO\GatewayRefund;
use App\Services\CRM\Payments\Gateways\DTO\NormalizedEvent;
use RuntimeException;

// BRD: CRM-FM-003 — CCAvenue adapter (stub; full impl deferred).
final class CCAvenueGateway implements PaymentGatewayInterface
{
    /** @param array<string,mixed> $config */
    public function __construct(private readonly array $config) {}

    public function provider(): string
    {
        return 'ccavenue';
    }

    public function createOrder(PaymentTransaction $transaction): GatewayOrder
    {
        throw new RuntimeException('CCAvenue adapter not yet implemented.');
    }

    public function verifySignature(string $rawPayload, array $headers): bool
    {
        return false;
    }

    public function parseWebhook(array $payload): NormalizedEvent
    {
        throw new RuntimeException('CCAvenue adapter not yet implemented.');
    }

    public function fetchStatus(string $gatewayPaymentId): NormalizedEvent
    {
        throw new RuntimeException('CCAvenue adapter not yet implemented.');
    }

    public function initiateRefund(RefundRequest $refund): GatewayRefund
    {
        throw new RuntimeException('CCAvenue adapter not yet implemented.');
    }
}
