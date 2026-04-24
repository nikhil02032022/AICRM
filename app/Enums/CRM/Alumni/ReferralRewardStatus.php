<?php

declare(strict_types=1);

namespace App\Enums\CRM\Alumni;

// BRD: CRM-AL-003 — Tracks the reward accrual lifecycle for a referral code
enum ReferralRewardStatus: string
{
    case Pending   = 'pending';
    case Earned    = 'earned';
    case Disbursed = 'disbursed';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Earned    => 'Earned',
            self::Disbursed => 'Disbursed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending   => 'badge-blue',
            self::Earned    => 'badge-green',
            self::Disbursed => 'badge-indigo',
        };
    }

    public function dotColour(): string
    {
        return match ($this) {
            self::Pending   => 'bg-blue-400',
            self::Earned    => 'bg-green-500',
            self::Disbursed => 'bg-indigo-500',
        };
    }

    public function textColour(): string
    {
        return match ($this) {
            self::Pending   => 'text-blue-700',
            self::Earned    => 'text-green-700',
            self::Disbursed => 'text-indigo-700',
        };
    }

    public function bgColour(): string
    {
        return match ($this) {
            self::Pending   => 'bg-blue-50',
            self::Earned    => 'bg-green-50',
            self::Disbursed => 'bg-indigo-50',
        };
    }
}
