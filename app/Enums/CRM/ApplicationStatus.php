<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AP-008 — Application pipeline stages track admitted applicants from submission to enrolment
enum ApplicationStatus: string
{
    case UNDER_REVIEW = 'under_review';
    case SHORTLISTED = 'shortlisted';
    case WAITLISTED = 'waitlisted';
    case OFFER_ISSUED = 'offer_issued';
    case OFFER_ACCEPTED = 'offer_accepted';
    case OFFER_DECLINED = 'offer_declined';
    case ENROLLED = 'enrolled';
    case WITHDRAWN = 'withdrawn';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::UNDER_REVIEW => 'Under Review',
            self::SHORTLISTED => 'Shortlisted',
            self::WAITLISTED => 'Waitlisted',
            self::OFFER_ISSUED => 'Offer Issued',
            self::OFFER_ACCEPTED => 'Offer Accepted',
            self::OFFER_DECLINED => 'Offer Declined',
            self::ENROLLED => 'Enrolled',
            self::WITHDRAWN => 'Withdrawn',
            self::REJECTED => 'Rejected',
        };
    }

    public function badgeColour(): string
    {
        return match ($this) {
            self::UNDER_REVIEW => 'blue',
            self::SHORTLISTED => 'cyan',
            self::WAITLISTED => 'yellow',
            self::OFFER_ISSUED => 'purple',
            self::OFFER_ACCEPTED => 'green',
            self::OFFER_DECLINED => 'orange',
            self::ENROLLED => 'emerald',
            self::WITHDRAWN => 'slate',
            self::REJECTED => 'red',
        };
    }

    /**
     * Allowed transitions from this status.
     * Returns array of ApplicationStatus cases this status can transition to.
     * BRD: CRM-AP-009 — Pipeline stage transitions are governed by state machine rules
     */
    public function transitionsTo(): array
    {
        return match ($this) {
            self::UNDER_REVIEW => [self::SHORTLISTED, self::REJECTED],
            self::SHORTLISTED => [self::WAITLISTED, self::OFFER_ISSUED, self::REJECTED],
            self::WAITLISTED => [self::OFFER_ISSUED, self::REJECTED],
            self::OFFER_ISSUED => [self::OFFER_ACCEPTED, self::OFFER_DECLINED],
            self::OFFER_ACCEPTED => [self::ENROLLED, self::WITHDRAWN],
            self::OFFER_DECLINED => [self::WITHDRAWN],
            self::ENROLLED => [self::WITHDRAWN],
            self::WITHDRAWN => [],
            self::REJECTED => [],
        };
    }

    /**
     * Is this status a terminal state?
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::ENROLLED, self::WITHDRAWN, self::REJECTED]);
    }

    /**
     * Is this status allowed to accept payments?
     */
    public function canAcceptPayment(): bool
    {
        return in_array($this, [self::OFFER_ISSUED, self::OFFER_ACCEPTED]);
    }
}
