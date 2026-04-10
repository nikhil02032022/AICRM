<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-002 — Email/SMS campaign lifecycle statuses
enum CampaignStatus: string
{
    case DRAFT     = 'DRAFT';
    case SCHEDULED = 'SCHEDULED';
    case SENDING   = 'SENDING';
    case SENT      = 'SENT';
    case PAUSED    = 'PAUSED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match($this) {
            self::DRAFT     => 'Draft',
            self::SCHEDULED => 'Scheduled',
            self::SENDING   => 'Sending',
            self::SENT      => 'Sent',
            self::PAUSED    => 'Paused',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function colour(): string
    {
        return match($this) {
            self::DRAFT     => 'gray',
            self::SCHEDULED => 'blue',
            self::SENDING   => 'yellow',
            self::SENT      => 'green',
            self::PAUSED    => 'orange',
            self::CANCELLED => 'red',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::SCHEDULED, self::PAUSED], strict: true);
    }
}
