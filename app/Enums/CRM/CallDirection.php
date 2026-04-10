<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-016 — Call direction for telephony
enum CallDirection: string
{
    case INBOUND  = 'INBOUND';
    case OUTBOUND = 'OUTBOUND';

    public function label(): string
    {
        return match($this) {
            self::INBOUND  => 'Inbound',
            self::OUTBOUND => 'Outbound',
        };
    }
}
