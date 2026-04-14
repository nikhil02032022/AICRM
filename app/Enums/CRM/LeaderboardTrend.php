<?php

declare(strict_types=1);

namespace App\Enums\CRM;

/**
 * BRD: CRM-EC-010 — Leaderboard rank trend indicator
 */
enum LeaderboardTrend: string
{
    case UP = 'up';
    case DOWN = 'down';
    case STABLE = 'stable';

    public function label(): string
    {
        return match ($this) {
            self::UP => 'Rising',
            self::DOWN => 'Falling',
            self::STABLE => 'Stable',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::UP => '↑',
            self::DOWN => '↓',
            self::STABLE => '→',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UP => 'green',
            self::DOWN => 'red',
            self::STABLE => 'gray',
        };
    }
}
