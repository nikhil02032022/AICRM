<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-008 — DLT template registration workflow statuses
enum DltTemplateStatus: string
{
    case DRAFT            = 'DRAFT';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
    case APPROVED         = 'APPROVED';
    case REJECTED         = 'REJECTED';

    public function label(): string
    {
        return match($this) {
            self::DRAFT            => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED         => 'Approved',
            self::REJECTED         => 'Rejected',
        };
    }

    public function colour(): string
    {
        return match($this) {
            self::DRAFT            => 'gray',
            self::PENDING_APPROVAL => 'yellow',
            self::APPROVED         => 'green',
            self::REJECTED         => 'red',
        };
    }

    public function canSendSms(): bool
    {
        return $this === self::APPROVED;
    }
}
