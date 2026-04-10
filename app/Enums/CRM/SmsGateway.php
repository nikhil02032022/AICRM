<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-007 — Configurable SMS gateway adapters
enum SmsGateway: string
{
    case MSG91     = 'MSG91';
    case TEXTLOCAL = 'TEXTLOCAL';
    case KALEYRA   = 'KALEYRA';

    public function label(): string
    {
        return match($this) {
            self::MSG91     => 'MSG91',
            self::TEXTLOCAL => 'Textlocal',
            self::KALEYRA   => 'Kaleyra',
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
