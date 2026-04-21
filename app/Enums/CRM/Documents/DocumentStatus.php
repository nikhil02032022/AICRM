<?php

declare(strict_types=1);

namespace App\Enums\CRM\Documents;

// BRD: CRM-DM-003 — Document status tracking
enum DocumentStatus: string
{
    case NOT_SUBMITTED = 'not_submitted';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function isPending(): bool
    {
        return in_array($this, [self::NOT_SUBMITTED, self::SUBMITTED, self::UNDER_REVIEW], true);
    }

    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::VERIFIED, self::REJECTED], true);
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
