<?php

namespace App\Enums\CRM\Compliance;

enum OptOutChannel: string
{
    case Email    = 'email';
    case SMS      = 'sms';
    case WhatsApp = 'whatsapp';
    case All      = 'all';

    public function label(): string
    {
        return match($this) {
            self::Email    => 'Email',
            self::SMS      => 'SMS',
            self::WhatsApp => 'WhatsApp',
            self::All      => 'All Channels',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Email    => 'badge-blue',
            self::SMS      => 'badge-amber',
            self::WhatsApp => 'badge-green',
            self::All      => 'badge-red',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Email    => 'blue',
            self::SMS      => 'amber',
            self::WhatsApp => 'green',
            self::All      => 'red',
        };
    }
}
