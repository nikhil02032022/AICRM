<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-TC-001 — Per-lead status inside a dialler session queue
enum DiallerLogStatus: string
{
    case QUEUED = 'QUEUED';
    case PLACED = 'PLACED';
    case SKIPPED = 'SKIPPED';
    case FAILED = 'FAILED';
}
