<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AG-008 — Supported channels for agent bulk communications
enum AgentCommsChannel: string
{
    case EMAIL     = 'email';
    case WHATSAPP  = 'whatsapp';
    case SMS       = 'sms';

    public function label(): string
    {
        return match($this) {
            self::EMAIL    => 'Email',
            self::WHATSAPP => 'WhatsApp',
            self::SMS      => 'SMS',
        };
    }
}
