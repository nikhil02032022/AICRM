<?php

namespace App\Enums\CRM\Compliance;

enum PiiErasureStatus: string
{
    case Pending   = 'pending';
    case Scheduled = 'scheduled';
    case Erased    = 'erased';
    case Failed    = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Scheduled => 'Scheduled',
            self::Erased    => 'Erased',
            self::Failed    => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending   => 'badge-gray',
            self::Scheduled => 'badge-amber',
            self::Erased    => 'badge-green',
            self::Failed    => 'badge-red',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'gray',
            self::Scheduled => 'amber',
            self::Erased    => 'green',
            self::Failed    => 'red',
        };
    }
}
