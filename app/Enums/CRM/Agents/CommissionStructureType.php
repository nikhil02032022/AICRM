<?php

declare(strict_types=1);

namespace App\Enums\CRM\Agents;

// BRD: CRM-AG-004 — Commission structure calculation method per agent agreement
enum CommissionStructureType: string
{
    case PerEnrolment    = 'per_enrolment';
    case PerApplication  = 'per_application';
    case PercentageFee   = 'percentage_fee';

    public function label(): string
    {
        return match ($this) {
            self::PerEnrolment   => 'Per Enrolment (Fixed)',
            self::PerApplication => 'Per Application (Fixed)',
            self::PercentageFee  => 'Percentage of Fee',
        };
    }

    public function requiresPercentage(): bool
    {
        return $this === self::PercentageFee;
    }

    public function requiresAmount(): bool
    {
        return $this !== self::PercentageFee;
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
