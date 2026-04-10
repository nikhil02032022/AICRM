<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-004 — Email provider for sender domain configuration
enum EmailProvider: string
{
    case MAILGUN  = 'MAILGUN';
    case POSTMARK = 'POSTMARK';
    case SES      = 'SES';
    case SENDGRID = 'SENDGRID';

    public function label(): string
    {
        return match($this) {
            self::MAILGUN  => 'Mailgun',
            self::POSTMARK => 'Postmark',
            self::SES      => 'AWS SES',
            self::SENDGRID => 'SendGrid',
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
