<?php

namespace App\Enums\CRM\Admin;

enum BackupStatus: string
{
    case Running   = 'running';
    case Completed = 'completed';
    case Failed    = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Running   => 'Running',
            self::Completed => 'Completed',
            self::Failed    => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Running   => 'badge-blue',
            self::Completed => 'badge-green',
            self::Failed    => 'badge-red',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Running   => 'blue',
            self::Completed => 'green',
            self::Failed    => 'red',
        };
    }
}
