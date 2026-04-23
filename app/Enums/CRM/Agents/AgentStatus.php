<?php

declare(strict_types=1);

namespace App\Enums\CRM\Agents;

// BRD: CRM-AG-001 — Agent lifecycle status
enum AgentStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Active',
            self::Inactive  => 'Inactive',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeColour(): string
    {
        return match ($this) {
            self::Active    => 'green',
            self::Inactive  => 'gray',
            self::Suspended => 'red',
        };
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return array_column(
            array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases()),
            'label',
            'value',
        );
    }
}
