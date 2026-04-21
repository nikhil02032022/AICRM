<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-MA-003 — Legacy status enum kept for backward-compat with AutomationActionService.
// New task management code must use App\Enums\CRM\Tasks\TaskStatus instead.
enum TaskStatus: string
{
    case OPEN = 'open';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
