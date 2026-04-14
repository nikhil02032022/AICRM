<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EC-002 — Individual status of a lead's interest in a specific programme
enum ProgrammeInterestStatus: string
{
    case INTERESTED           = 'interested';
    case COUNSELLING_SCHEDULED = 'counselling_scheduled';
    case COUNSELLING_DONE     = 'counselling_done';
    case APPLICATION_STARTED  = 'application_started';
    case APPLICATION_SUBMITTED = 'application_submitted';
    case OFFER_ISSUED         = 'offer_issued';
    case ENROLLED             = 'enrolled';
    case DEFERRED             = 'deferred';
    case LOST                 = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::INTERESTED            => 'Interested',
            self::COUNSELLING_SCHEDULED => 'Counselling Scheduled',
            self::COUNSELLING_DONE      => 'Counselling Done',
            self::APPLICATION_STARTED   => 'Application Started',
            self::APPLICATION_SUBMITTED => 'Application Submitted',
            self::OFFER_ISSUED          => 'Offer Issued',
            self::ENROLLED              => 'Enrolled',
            self::DEFERRED              => 'Deferred',
            self::LOST                  => 'Lost',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::INTERESTED            => 'bg-blue-100 text-blue-700',
            self::COUNSELLING_SCHEDULED => 'bg-violet-100 text-violet-700',
            self::COUNSELLING_DONE      => 'bg-indigo-100 text-indigo-700',
            self::APPLICATION_STARTED   => 'bg-amber-100 text-amber-700',
            self::APPLICATION_SUBMITTED => 'bg-orange-100 text-orange-700',
            self::OFFER_ISSUED          => 'bg-cyan-100 text-cyan-700',
            self::ENROLLED              => 'bg-green-100 text-green-700',
            self::DEFERRED              => 'bg-gray-100 text-gray-600',
            self::LOST                  => 'bg-red-100 text-red-700',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::ENROLLED, self::LOST], strict: true);
    }

    /** @return list<array{value: string, label: string}> */
    public static function optionsForSelect(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
