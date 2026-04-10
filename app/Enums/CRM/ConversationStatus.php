<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-012 — WhatsApp conversation status in shared inbox
enum ConversationStatus: string
{
    case OPEN     = 'OPEN';
    case PENDING  = 'PENDING';
    case RESOLVED = 'RESOLVED';
    case EXPIRED  = 'EXPIRED';

    public function label(): string
    {
        return match($this) {
            self::OPEN     => 'Open',
            self::PENDING  => 'Pending',
            self::RESOLVED => 'Resolved',
            self::EXPIRED  => 'Expired (24h window)',
        };
    }

    public function colour(): string
    {
        return match($this) {
            self::OPEN     => 'green',
            self::PENDING  => 'yellow',
            self::RESOLVED => 'gray',
            self::EXPIRED  => 'red',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::OPEN, self::PENDING], strict: true);
    }
}
