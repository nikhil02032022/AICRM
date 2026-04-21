<?php

declare(strict_types=1);

namespace App\Enums\CRM\Tasks;

// BRD: CRM-TF-001 to CRM-TF-004 — Full task lifecycle status
enum TaskStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Overdue    = 'overdue';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed  => 'Completed',
            self::Overdue    => 'Overdue',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Pending    => 'yellow',
            self::InProgress => 'blue',
            self::Completed  => 'green',
            self::Overdue    => 'red',
            self::Cancelled  => 'gray',
        };
    }

    public function tailwindBadgeClass(): string
    {
        return match ($this) {
            self::Pending    => 'bg-yellow-100 text-yellow-800',
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Completed  => 'bg-green-100 text-green-800',
            self::Overdue    => 'bg-red-100 text-red-800',
            self::Cancelled  => 'bg-gray-100 text-gray-600',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled => true,
            default                          => false,
        };
    }
}
