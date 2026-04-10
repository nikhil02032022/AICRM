<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LC-001 — Lead status lifecycle tracks a lead from first enquiry to enrolment
enum LeadStatus: string
{
    case NEW_ENQUIRY = 'new_enquiry';
    case CONTACTED = 'contacted';
    case COUNSELLING_SCHEDULED = 'counselling_scheduled';
    case COUNSELLING_DONE = 'counselling_done';
    case APPLICATION_STARTED = 'application_started';
    case APPLICATION_SUBMITTED = 'application_submitted';
    case OFFER_ISSUED = 'offer_issued';
    case FEE_PAID = 'fee_paid';
    case ENROLLED = 'enrolled';
    case DEFERRED = 'deferred';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::NEW_ENQUIRY => 'New Enquiry',
            self::CONTACTED => 'Contacted',
            self::COUNSELLING_SCHEDULED => 'Counselling Scheduled',
            self::COUNSELLING_DONE => 'Counselling Done',
            self::APPLICATION_STARTED => 'Application Started',
            self::APPLICATION_SUBMITTED => 'Application Submitted',
            self::OFFER_ISSUED => 'Offer Issued',
            self::FEE_PAID => 'Fee Paid',
            self::ENROLLED => 'Enrolled',
            self::DEFERRED => 'Deferred',
            self::LOST => 'Lost',
        };
    }

    public function badgeColour(): string
    {
        return match ($this) {
            self::NEW_ENQUIRY => 'blue',
            self::CONTACTED => 'indigo',
            self::COUNSELLING_SCHEDULED => 'violet',
            self::COUNSELLING_DONE => 'purple',
            self::APPLICATION_STARTED => 'amber',
            self::APPLICATION_SUBMITTED => 'orange',
            self::OFFER_ISSUED => 'cyan',
            self::FEE_PAID => 'teal',
            self::ENROLLED => 'green',
            self::DEFERRED => 'yellow',
            self::LOST => 'red',
        };
    }

    /** Can this status be converted to an ERP Student Master record? */
    public function isConvertible(): bool
    {
        return $this === self::FEE_PAID || $this === self::ENROLLED;
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** @return list<LeadStatus> */
    private function allowedTransitions(): array
    {
        return match ($this) {
            self::NEW_ENQUIRY => [self::CONTACTED, self::LOST],
            self::CONTACTED => [self::COUNSELLING_SCHEDULED, self::LOST],
            self::COUNSELLING_SCHEDULED => [self::COUNSELLING_DONE, self::CONTACTED, self::LOST],
            self::COUNSELLING_DONE => [self::APPLICATION_STARTED, self::LOST],
            self::APPLICATION_STARTED => [self::APPLICATION_SUBMITTED, self::LOST],
            self::APPLICATION_SUBMITTED => [self::OFFER_ISSUED, self::LOST],
            self::OFFER_ISSUED => [self::FEE_PAID, self::DEFERRED, self::LOST],
            self::FEE_PAID => [self::ENROLLED],
            self::ENROLLED => [],
            self::DEFERRED => [self::APPLICATION_SUBMITTED, self::LOST],
            self::LOST => [],
        };
    }
}
