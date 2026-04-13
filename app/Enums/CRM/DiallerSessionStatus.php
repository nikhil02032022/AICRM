<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-TC-001 — Auto-dialler session lifecycle states
enum DiallerSessionStatus: string
{
    case QUEUED = 'QUEUED';
    case ACTIVE = 'ACTIVE';
    case STOPPED = 'STOPPED';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::ACTIVE => 'Active',
            self::STOPPED => 'Stopped',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }
}
