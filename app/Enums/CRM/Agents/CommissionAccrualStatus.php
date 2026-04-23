<?php

declare(strict_types=1);

namespace App\Enums\CRM\Agents;

// BRD: CRM-AG-005 — Auto-accrued commission lifecycle status
enum CommissionAccrualStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Paid     = 'paid';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pending',
            self::Approved => 'Approved',
            self::Paid     => 'Paid',
            self::Reversed => 'Reversed',
        };
    }

    public function badgeColour(): string
    {
        return match ($this) {
            self::Pending  => 'amber',
            self::Approved => 'blue',
            self::Paid     => 'green',
            self::Reversed => 'red',
        };
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::Approved, self::Paid], strict: true);
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return array_column(
            array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases()),
            'label',
            'value',
        );
    }
}
