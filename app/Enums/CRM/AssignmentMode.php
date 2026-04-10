<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EC-006 — Lead auto-assignment strategies
enum AssignmentMode: string
{
    case ROUND_ROBIN = 'round_robin';
    case LOAD_BALANCED = 'load_balanced';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::ROUND_ROBIN => 'Round Robin',
            self::LOAD_BALANCED => 'Load Balanced',
            self::MANUAL => 'Manual Only',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ROUND_ROBIN => 'Leads are assigned in rotation to available counsellors.',
            self::LOAD_BALANCED => 'Leads are assigned to the counsellor with the fewest active leads.',
            self::MANUAL => 'Admissions managers assign leads manually — no auto-assignment.',
        };
    }
}
