<?php

declare(strict_types=1);

namespace App\Enums\CRM\Counselling;

// BRD: CRM-EC-018 — Video meeting provider strategy selector
enum VideoProvider: string
{
    case GOOGLE_MEET = 'google_meet';
    case ZOOM = 'zoom';
    case WEB_RTC = 'webrtc';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::GOOGLE_MEET => 'Google Meet',
            self::ZOOM => 'Zoom',
            self::WEB_RTC => 'WebRTC',
            self::NONE => 'None',
        };
    }

    /** Returns true for providers that generate an external meeting URL. */
    public function isExternal(): bool
    {
        return in_array($this, [self::GOOGLE_MEET, self::ZOOM], true);
    }
}
