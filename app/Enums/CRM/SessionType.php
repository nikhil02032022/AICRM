<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EC-015 — Session types (initial, follow-up, group)
enum SessionType: string
{
    case INITIAL = 'initial';
    case FOLLOW_UP = 'follow_up';
    case GROUP = 'group';
    case WALK_IN = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL => 'Initial Counselling',
            self::FOLLOW_UP => 'Follow-up',
            self::GROUP => 'Group Session',
            self::WALK_IN => 'Walk-in',
        };
    }
}
