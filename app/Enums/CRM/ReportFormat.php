<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AR-020 — Export format for scheduled report delivery
enum ReportFormat: string
{
    case CSV   = 'csv';
    case EXCEL = 'excel';
    case PDF   = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::CSV   => 'CSV',
            self::EXCEL => 'Excel (.xlsx)',
            self::PDF   => 'PDF',
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
