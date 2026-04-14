<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AG-006 — Agent commission approval/payout workflow status
enum CommissionStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PAID     = 'paid';

    public function label(): string
    {
        return match($this) {
            self::PENDING  => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::PAID     => 'Paid',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING  => 'amber',
            self::APPROVED => 'blue',
            self::REJECTED => 'red',
            self::PAID     => 'green',
        };
    }
}
