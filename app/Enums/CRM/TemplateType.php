<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-001 — Template types for communication templates
enum TemplateType: string
{
    case TRANSACTIONAL = 'TRANSACTIONAL';
    case MARKETING     = 'MARKETING';
    case OTP           = 'OTP';
    case NOTIFICATION  = 'NOTIFICATION';

    public function label(): string
    {
        return match($this) {
            self::TRANSACTIONAL => 'Transactional',
            self::MARKETING     => 'Marketing',
            self::OTP           => 'OTP',
            self::NOTIFICATION  => 'Notification',
        };
    }

    /** Marketing type requires {{unsubscribe_link}} tag — DPDP */
    public function requiresUnsubscribeLink(): bool
    {
        return $this === self::MARKETING;
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
