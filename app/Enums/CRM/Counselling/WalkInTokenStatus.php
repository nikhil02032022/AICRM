<?php

declare(strict_types=1);

namespace App\Enums\CRM\Counselling;

// BRD: CRM-EC-019 — Walk-in token lifecycle states
enum WalkInTokenStatus: string
{
    case WAITING = 'waiting';
    case CALLED = 'called';
    case SERVING = 'serving';
    case SERVED = 'served';
    case SKIPPED = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Waiting',
            self::CALLED => 'Called',
            self::SERVING => 'Serving',
            self::SERVED => 'Served',
            self::SKIPPED => 'Skipped',
        };
    }

    public function badgeColour(): string
    {
        return match ($this) {
            self::WAITING => 'blue',
            self::CALLED => 'yellow',
            self::SERVING => 'indigo',
            self::SERVED => 'green',
            self::SKIPPED => 'slate',
        };
    }

    /** Served and Skipped are final — no further transitions allowed. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::SERVED, self::SKIPPED], true);
    }
}
