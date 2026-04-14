<?php

declare(strict_types=1);

namespace App\Enums\CRM;

/**
 * BRD: CRM-EC-010 — Badge category types for gamification
 */
enum BadgeCategory: string
{
    case PERFORMANCE = 'performance';
    case MILESTONE = 'milestone';
    case CONSISTENCY = 'consistency';
    case EXCELLENCE = 'excellence';
    case SPECIAL = 'special';

    public function label(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'Performance',
            self::MILESTONE => 'Milestone',
            self::CONSISTENCY => 'Consistency',
            self::EXCELLENCE => 'Excellence',
            self::SPECIAL => 'Special',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'blue',
            self::MILESTONE => 'purple',
            self::CONSISTENCY => 'green',
            self::EXCELLENCE => 'yellow',
            self::SPECIAL => 'red',
        };
    }
}
