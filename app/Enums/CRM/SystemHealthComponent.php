<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-SA-011 — Monitored system components for the health dashboard
enum SystemHealthComponent: string
{
    case QUEUE       = 'queue';
    case REDIS       = 'redis';
    case DATABASE    = 'database';
    case HORIZON     = 'horizon';
    case S3          = 's3';
    case AI_API      = 'ai_api';
    case MAIL        = 'mail';
    case SMS_GATEWAY = 'sms_gateway';

    public function label(): string
    {
        return match ($this) {
            self::QUEUE       => 'Job Queue',
            self::REDIS       => 'Redis Cache',
            self::DATABASE    => 'Database',
            self::HORIZON     => 'Laravel Horizon',
            self::S3          => 'S3 Document Storage',
            self::AI_API      => 'Anthropic AI API',
            self::MAIL        => 'Mail Gateway',
            self::SMS_GATEWAY => 'SMS Gateway',
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
