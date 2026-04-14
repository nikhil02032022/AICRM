<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-DM-006 — DigiLocker document verification status lifecycle
enum DigiLockerStatus: string
{
    case PENDING    = 'pending';
    case REQUESTED  = 'requested';
    case SHARED     = 'shared';
    case VERIFIED   = 'verified';
    case REJECTED   = 'rejected';
    case FAILED     = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Pending',
            self::REQUESTED => 'Request Sent',
            self::SHARED    => 'Document Shared',
            self::VERIFIED  => 'Verified',
            self::REJECTED  => 'Rejected',
            self::FAILED    => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING   => 'gray',
            self::REQUESTED => 'blue',
            self::SHARED    => 'indigo',
            self::VERIFIED  => 'green',
            self::REJECTED  => 'red',
            self::FAILED    => 'red',
        };
    }
}
