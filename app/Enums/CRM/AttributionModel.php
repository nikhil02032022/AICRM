<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LC-016 — Supported attribution models for credit allocation and CPL reporting.
enum AttributionModel: string
{
    case FIRST_TOUCH = 'first_touch';
    case LAST_TOUCH = 'last_touch';
    case LINEAR = 'linear';

    public function label(): string
    {
        return match ($this) {
            self::FIRST_TOUCH => 'First Touch',
            self::LAST_TOUCH => 'Last Touch',
            self::LINEAR => 'Linear',
        };
    }
}
