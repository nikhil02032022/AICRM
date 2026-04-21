<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Gateways\DTO;

// BRD: CRM-FM-011 — Normalized refund response
final class GatewayRefund
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public readonly string $gatewayRefundId,
        public readonly string $status,
        public readonly array $raw = [],
    ) {}
}
