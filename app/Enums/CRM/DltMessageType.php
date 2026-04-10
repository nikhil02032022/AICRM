<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-008 — TRAI DLT message types
enum DltMessageType: string
{
    case TRANSACTIONAL = 'TRANSACTIONAL';
    case PROMOTIONAL   = 'PROMOTIONAL';
    case OTP           = 'OTP';
    case SERVICE       = 'SERVICE';

    public function label(): string
    {
        return match($this) {
            self::TRANSACTIONAL => 'Transactional',
            self::PROMOTIONAL   => 'Promotional',
            self::OTP           => 'OTP',
            self::SERVICE       => 'Service',
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
