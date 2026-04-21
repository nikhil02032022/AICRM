<?php

declare(strict_types=1);

namespace App\Enums\CRM\Payments;

// BRD: CRM-FM-010 — Payment reminder dispatch state
enum ReminderStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';
}
