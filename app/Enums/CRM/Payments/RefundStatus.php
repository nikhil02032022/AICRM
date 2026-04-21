<?php

declare(strict_types=1);

namespace App\Enums\CRM\Payments;

// BRD: CRM-FM-011 — Refund request approval lifecycle
enum RefundStatus: string
{
    case PENDING = 'pending';
    case MANAGER_APPROVED = 'manager_approved';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROCESSED = 'processed';
    case FAILED = 'failed';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
