<?php

namespace App\Enums\CRM\Alumni;

enum AlumniPipelineStatus: string
{
    case Pending  = 'pending';
    case Eligible = 'eligible';
    case Synced   = 'synced';
    case Failed   = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Pending',
            self::Eligible => 'Eligible',
            self::Synced   => 'Synced',
            self::Failed   => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending  => 'badge-gray',
            self::Eligible => 'badge-blue',
            self::Synced   => 'badge-green',
            self::Failed   => 'badge-red',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending  => 'gray',
            self::Eligible => 'blue',
            self::Synced   => 'green',
            self::Failed   => 'red',
        };
    }
}
