<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Gateways;

use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\RefundRequest;
use App\Services\CRM\Payments\Gateways\DTO\GatewayOrder;
use App\Services\CRM\Payments\Gateways\DTO\GatewayRefund;
use App\Services\CRM\Payments\Gateways\DTO\NormalizedEvent;

// BRD: CRM-FM-003 — Common contract for all payment gateway adapters
interface PaymentGatewayInterface
{
    public function provider(): string;

    public function createOrder(PaymentTransaction $transaction): GatewayOrder;

    /**
     * @param array<string,string> $headers
     */
    public function verifySignature(string $rawPayload, array $headers): bool;

    /**
     * @param array<string,mixed> $payload
     */
    public function parseWebhook(array $payload): NormalizedEvent;

    public function fetchStatus(string $gatewayPaymentId): NormalizedEvent;

    public function initiateRefund(RefundRequest $refund): GatewayRefund;
}
