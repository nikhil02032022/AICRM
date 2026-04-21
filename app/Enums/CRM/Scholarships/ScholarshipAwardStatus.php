<?php

declare(strict_types=1);

namespace App\Enums\CRM\Scholarships;

// BRD: CRM-FM-008 — Approval workflow lifecycle
enum ScholarshipAwardStatus: string
{
    case DRAFT = 'draft';
    case COUNSELLOR_SUBMITTED = 'counsellor_submitted';
    case MANAGER_APPROVED = 'manager_approved';
    case FINANCE_APPROVED = 'finance_approved';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';

    public function isPending(): bool
    {
        return in_array($this, [self::DRAFT, self::COUNSELLOR_SUBMITTED, self::MANAGER_APPROVED], true);
    }

    public function isApproved(): bool
    {
        return $this === self::FINANCE_APPROVED;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FINANCE_APPROVED, self::REJECTED, self::WITHDRAWN], true);
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
