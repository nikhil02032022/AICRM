<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EC-005 — Entities that can carry institution-defined custom fields
enum CustomFieldEntity: string
{
    case LEAD        = 'lead';
    case APPLICATION = 'application';

    public function label(): string
    {
        return match ($this) {
            self::LEAD        => 'Lead',
            self::APPLICATION => 'Application',
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
