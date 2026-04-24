<?php

namespace App\Enums\CRM\Compliance;

enum DataAccessStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Delivered  = 'delivered';
    case Failed     = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pending',
            self::Processing => 'Processing',
            self::Delivered  => 'Delivered',
            self::Failed     => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending    => 'badge-gray',
            self::Processing => 'badge-blue',
            self::Delivered  => 'badge-green',
            self::Failed     => 'badge-red',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending    => 'gray',
            self::Processing => 'blue',
            self::Delivered  => 'green',
            self::Failed     => 'red',
        };
    }
}
