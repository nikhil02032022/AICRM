<?php

declare(strict_types=1);

namespace App\Enums\CRM\Payments;

// BRD: CRM-FM-009 — Installment schedule row lifecycle
enum InstallmentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case WAIVED = 'waived';

    public function isOpen(): bool
    {
        return in_array($this, [self::PENDING, self::OVERDUE], true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
