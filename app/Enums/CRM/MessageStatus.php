<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-003 — Email/SMS/WhatsApp delivery status tracking
enum MessageStatus: string
{
    case PENDING       = 'PENDING';
    case SENT          = 'SENT';
    case DELIVERED     = 'DELIVERED';
    case READ          = 'READ';
    case FAILED        = 'FAILED';
    case BOUNCED       = 'BOUNCED';
    case UNSUBSCRIBED  = 'UNSUBSCRIBED';

    public function label(): string
    {
        return match($this) {
            self::PENDING      => 'Pending',
            self::SENT         => 'Sent',
            self::DELIVERED    => 'Delivered',
            self::READ         => 'Read',
            self::FAILED       => 'Failed',
            self::BOUNCED      => 'Bounced',
            self::UNSUBSCRIBED => 'Unsubscribed',
        };
    }

    public function colour(): string
    {
        return match($this) {
            self::PENDING      => 'yellow',
            self::SENT         => 'blue',
            self::DELIVERED    => 'green',
            self::READ         => 'indigo',
            self::FAILED       => 'red',
            self::BOUNCED      => 'orange',
            self::UNSUBSCRIBED => 'gray',
        };
    }
}
