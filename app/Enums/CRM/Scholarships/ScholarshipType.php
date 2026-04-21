<?php

declare(strict_types=1);

namespace App\Enums\CRM\Scholarships;

// BRD: CRM-FM-006 — Configurable scholarship and fee waiver categories
enum ScholarshipType: string
{
    case MERIT = 'merit';
    case SPORTS = 'sports';
    case MANAGEMENT_QUOTA = 'management_quota';
    case EARLY_BIRD = 'early_bird';
    case SIBLING = 'sibling';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::MERIT => 'Merit',
            self::SPORTS => 'Sports',
            self::MANAGEMENT_QUOTA => 'Management Quota',
            self::EARLY_BIRD => 'Early Bird',
            self::SIBLING => 'Sibling',
            self::CUSTOM => 'Custom',
        };
    }
}
