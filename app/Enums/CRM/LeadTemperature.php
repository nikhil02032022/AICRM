<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LQ-001 — Lead temperature classifies engagement intensity
enum LeadTemperature: string
{
    case HOT = 'hot';
    case WARM = 'warm';
    case COLD = 'cold';
    case LOST = 'lost';
    case CONVERTED = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::HOT => 'Hot',
            self::WARM => 'Warm',
            self::COLD => 'Cold',
            self::LOST => 'Lost',
            self::CONVERTED => 'Converted',
        };
    }

    public function badgeColour(): string
    {
        return match ($this) {
            self::HOT => 'red',
            self::WARM => 'orange',
            self::COLD => 'blue',
            self::LOST => 'gray',
            self::CONVERTED => 'green',
        };
    }

    /** Derive temperature from lead score (0–100). */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 75 => self::HOT,
            $score >= 50 => self::WARM,
            default => self::COLD,
        };
    }
}
