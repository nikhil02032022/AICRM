<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Gateways\DTO;

use App\Enums\CRM\Payments\PaymentStatus;

// BRD: CRM-FM-005 — Provider-agnostic event used by webhook + status pollers
final class NormalizedEvent
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly ?string $gatewayOrderId,
        public readonly ?string $gatewayPaymentId,
        public readonly PaymentStatus $status,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly ?string $failureReason = null,
        public readonly array $raw = [],
    ) {}
}
