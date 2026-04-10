<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-001 to CRM-CC-023 — Communication channels supported by the engine
enum CommunicationChannel: string
{
    case EMAIL = 'EMAIL';
    case SMS = 'SMS';
    case WHATSAPP = 'WHATSAPP';
    case VOICE = 'VOICE';
    case PUSH = 'PUSH';

    public function label(): string
    {
        return match($this) {
            self::EMAIL     => 'Email',
            self::SMS       => 'SMS',
            self::WHATSAPP  => 'WhatsApp',
            self::VOICE     => 'Voice / Call',
            self::PUSH      => 'Push Notification',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::EMAIL     => 'envelope',
            self::SMS       => 'chat-bubble-left',
            self::WHATSAPP  => 'chat-bubble-left-right',
            self::VOICE     => 'phone',
            self::PUSH      => 'bell',
        };
    }

    /** @return array<string, string> */
    public static function optionsForSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
