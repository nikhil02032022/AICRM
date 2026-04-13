<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-TC-006 — Telecalling campaign lifecycle
enum TelecallingCampaignStatus: string
{
    case DRAFT = 'DRAFT';
    case SCHEDULED = 'SCHEDULED';
    case ACTIVE = 'ACTIVE';
    case PAUSED = 'PAUSED';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SCHEDULED => 'Scheduled',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::COMPLETED => 'Completed',
        };
    }
}
