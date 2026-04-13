<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-TC-005 — Supervisor monitor session lifecycle
enum CallMonitorStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ENDED = 'ENDED';
    case FAILED = 'FAILED';
}
