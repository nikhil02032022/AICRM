<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-TC-005 — Supervisor monitor modes
enum CallMonitorMode: string
{
    case LISTEN = 'LISTEN';
    case WHISPER = 'WHISPER';
    case BARGE_IN = 'BARGE_IN';

    public function label(): string
    {
        return match ($this) {
            self::LISTEN => 'Listen',
            self::WHISPER => 'Whisper',
            self::BARGE_IN => 'Barge-In',
        };
    }
}
