<?php

declare(strict_types=1);

namespace App\Enums\CRM\Payments;

// BRD: CRM-FM-005 — Payment transaction lifecycle
enum PaymentStatus: string
{
    case INITIATED = 'initiated';
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUND_PENDING = 'refund_pending';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public function isOpen(): bool
    {
        return in_array($this, [self::INITIATED, self::PENDING]);
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
