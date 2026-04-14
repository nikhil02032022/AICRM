<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AR-020 — Frequency options for scheduled report delivery
enum ReportFrequency: string
{
    case DAILY   = 'daily';
    case WEEKLY  = 'weekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY   => 'Daily',
            self::WEEKLY  => 'Weekly',
            self::MONTHLY => 'Monthly',
        };
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
