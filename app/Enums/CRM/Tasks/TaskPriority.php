<?php

declare(strict_types=1);

namespace App\Enums\CRM\Tasks;

// BRD: CRM-TF-003 — Daily task list sorted by priority
enum TaskPriority: string
{
    case Low    = 'low';
    case Normal = 'normal';
    case High   = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Normal => 'Normal',
            self::High   => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Low    => 'gray',
            self::Normal => 'blue',
            self::High   => 'orange',
            self::Urgent => 'red',
        };
    }

    public function tailwindBadgeClass(): string
    {
        return match ($this) {
            self::Low    => 'bg-gray-100 text-gray-600',
            self::Normal => 'bg-blue-100 text-blue-700',
            self::High   => 'bg-orange-100 text-orange-700',
            self::Urgent => 'bg-red-100 text-red-700',
        };
    }

    public function sortWeight(): int
    {
        return match ($this) {
            self::Low    => 1,
            self::Normal => 2,
            self::High   => 3,
            self::Urgent => 4,
        };
    }
}
