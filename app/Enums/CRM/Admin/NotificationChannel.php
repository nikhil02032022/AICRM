<?php

namespace App\Enums\CRM\Admin;

enum NotificationChannel: string
{
    case Email    = 'email';
    case SMS      = 'sms';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match($this) {
            self::Email    => 'Email',
            self::SMS      => 'SMS',
            self::WhatsApp => 'WhatsApp',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Email    => 'badge-blue',
            self::SMS      => 'badge-amber',
            self::WhatsApp => 'badge-green',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Email    => 'blue',
            self::SMS      => 'amber',
            self::WhatsApp => 'green',
        };
    }
}
