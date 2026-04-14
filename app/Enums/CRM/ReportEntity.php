<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AR-018 — Entities available as data sources in the custom report builder
enum ReportEntity: string
{
    case LEADS        = 'leads';
    case APPLICATIONS = 'applications';
    case ACTIVITIES   = 'activities';
    case PAYMENTS     = 'payments';

    public function label(): string
    {
        return match ($this) {
            self::LEADS        => 'Leads',
            self::APPLICATIONS => 'Applications',
            self::ACTIVITIES   => 'Activity Logs',
            self::PAYMENTS     => 'Payments',
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
