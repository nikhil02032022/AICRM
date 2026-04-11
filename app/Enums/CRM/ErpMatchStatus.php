<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LC-020 — ERP Student Master match status for a CRM lead
enum ErpMatchStatus: string
{
    case PENDING   = 'pending';
    case MATCHED   = 'matched';
    case NO_MATCH  = 'no_match';
    case ERROR     = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'ERP Check Pending',
            self::MATCHED  => 'ERP Student Match Found',
            self::NO_MATCH => 'No ERP Match',
            self::ERROR    => 'ERP Check Error',
        };
    }

    /** Tailwind colour class for badge background */
    public function badgeColour(): string
    {
        return match ($this) {
            self::PENDING  => 'yellow',
            self::MATCHED  => 'green',
            self::NO_MATCH => 'gray',
            self::ERROR    => 'red',
        };
    }
}
