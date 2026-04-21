<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Gateways\DTO;

// BRD: CRM-FM-003 — Normalized order returned from gateway createOrder
final class GatewayOrder
{
    /**
     * @param array<string,mixed> $rawResponse
     * @param array<string,mixed> $checkoutPayload
     */
    public function __construct(
        public readonly string $gatewayOrderId,
        public readonly string $checkoutUrl,
        public readonly array $checkoutPayload = [],
        public readonly array $rawResponse = [],
    ) {}
}
