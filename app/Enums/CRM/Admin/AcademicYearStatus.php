<?php

namespace App\Enums\CRM\Admin;

enum AcademicYearStatus: string
{
    case Active   = 'active';
    case Closed   = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Active',
            self::Closed   => 'Closed',
            self::Archived => 'Archived',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active   => 'badge-green',
            self::Closed   => 'badge-gray',
            self::Archived => 'badge-gray',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Active   => 'green',
            self::Closed   => 'gray',
            self::Archived => 'gray',
        };
    }
}
