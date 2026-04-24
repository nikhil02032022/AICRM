<?php

declare(strict_types=1);

namespace App\Enums\CRM\Alumni;

// BRD: CRM-AL-002 — Referral campaign lifecycle statuses
enum ReferralCampaignStatus: string
{
    case Draft  = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Ended  = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Draft  => 'Draft',
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Ended  => 'Ended',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft  => 'badge-slate',
            self::Active => 'badge-green',
            self::Paused => 'badge-amber',
            self::Ended  => 'badge-gray',
        };
    }

    public function dotColour(): string
    {
        return match ($this) {
            self::Draft  => 'bg-slate-400',
            self::Active => 'bg-green-500',
            self::Paused => 'bg-amber-400',
            self::Ended  => 'bg-gray-400',
        };
    }

    public function textColour(): string
    {
        return match ($this) {
            self::Draft  => 'text-slate-700',
            self::Active => 'text-green-700',
            self::Paused => 'text-amber-700',
            self::Ended  => 'text-gray-600',
        };
    }

    public function bgColour(): string
    {
        return match ($this) {
            self::Draft  => 'bg-slate-50',
            self::Active => 'bg-green-50',
            self::Paused => 'bg-amber-50',
            self::Ended  => 'bg-gray-50',
        };
    }
}
