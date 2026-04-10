<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-016 — Cloud telephony providers
enum TelephonyProvider: string
{
    case EXOTEL    = 'EXOTEL';
    case OZONETEL  = 'OZONETEL';
    case KNOWLARITY = 'KNOWLARITY';

    public function label(): string
    {
        return match($this) {
            self::EXOTEL     => 'Exotel',
            self::OZONETEL   => 'Ozonetel',
            self::KNOWLARITY => 'Knowlarity',
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
