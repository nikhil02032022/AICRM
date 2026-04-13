<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AI-004 — Sentiment classes used for inbound communication analysis
enum SentimentLabel: string
{
    case POSITIVE = 'positive';
    case NEUTRAL = 'neutral';
    case NEGATIVE = 'negative';

    public function label(): string
    {
        return match ($this) {
            self::POSITIVE => 'Positive',
            self::NEUTRAL => 'Neutral',
            self::NEGATIVE => 'Negative',
        };
    }
}
