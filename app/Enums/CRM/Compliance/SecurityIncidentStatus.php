<?php

namespace App\Enums\CRM\Compliance;

enum SecurityIncidentStatus: string
{
    case Reported      = 'reported';
    case Investigating = 'investigating';
    case Notified      = 'notified';
    case Resolved      = 'resolved';

    public function label(): string
    {
        return match($this) {
            self::Reported      => 'Reported',
            self::Investigating => 'Investigating',
            self::Notified      => 'Notified',
            self::Resolved      => 'Resolved',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Reported      => 'badge-red',
            self::Investigating => 'badge-amber',
            self::Notified      => 'badge-blue',
            self::Resolved      => 'badge-green',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Reported      => 'red',
            self::Investigating => 'amber',
            self::Notified      => 'blue',
            self::Resolved      => 'green',
        };
    }
}
