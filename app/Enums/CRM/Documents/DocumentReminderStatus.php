<?php

declare(strict_types=1);

namespace App\Enums\CRM\Documents;

// BRD: CRM-DM-005 — Pending document reminder lifecycle
enum DocumentReminderStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
