<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-SA-011 — Health status levels for system components
enum SystemHealthStatus: string
{
    case OK       = 'ok';
    case WARNING  = 'warning';
    case CRITICAL = 'critical';
    case UNKNOWN  = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::OK       => 'Operational',
            self::WARNING  => 'Degraded',
            self::CRITICAL => 'Outage',
            self::UNKNOWN  => 'Unknown',
        };
    }

    public function tailwindBadgeClass(): string
    {
        return match ($this) {
            self::OK       => 'bg-green-100 text-green-800',
            self::WARNING  => 'bg-yellow-100 text-yellow-800',
            self::CRITICAL => 'bg-red-100 text-red-800',
            self::UNKNOWN  => 'bg-gray-100 text-gray-600',
        };
    }
}
