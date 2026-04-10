<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EC-013 — Reason for loss is mandatory when a lead is marked as Lost
enum LostReason: string
{
    case NOT_INTERESTED = 'not_interested';
    case JOINED_COMPETITOR = 'joined_competitor';
    case FINANCIAL_CONSTRAINT = 'financial_constraint';
    case PERSONAL_REASON = 'personal_reason';
    case NO_RESPONSE = 'no_response';
    case PROGRAMME_NOT_SUITED = 'programme_not_suited';
    case DEFERRED_NEXT_CYCLE = 'deferred_next_cycle';

    public function label(): string
    {
        return match ($this) {
            self::NOT_INTERESTED => 'Not Interested',
            self::JOINED_COMPETITOR => 'Joined Competitor',
            self::FINANCIAL_CONSTRAINT => 'Financial Constraint',
            self::PERSONAL_REASON => 'Personal Reason',
            self::NO_RESPONSE => 'No Response',
            self::PROGRAMME_NOT_SUITED => 'Programme Not Suited',
            self::DEFERRED_NEXT_CYCLE => 'Deferred to Next Cycle',
        };
    }

    /** @return array<string, string> value => label for select dropdowns */
    public static function optionsForSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
