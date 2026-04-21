<?php

declare(strict_types=1);

namespace App\Enums\CRM\Payments;

// BRD: CRM-FM-004, CRM-FM-010 — Channels through which payment links/reminders are delivered
enum PaymentChannel: string
{
    case WHATSAPP = 'whatsapp';
    case SMS = 'sms';
    case EMAIL = 'email';

    public function label(): string
    {
        return match ($this) {
            self::WHATSAPP => 'WhatsApp',
            self::SMS => 'SMS',
            self::EMAIL => 'Email',
        };
    }
}
