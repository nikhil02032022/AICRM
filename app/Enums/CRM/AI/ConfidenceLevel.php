<?php

declare(strict_types=1);

namespace App\Enums\CRM\AI;

// BRD: CRM-AI-001 — Confidence tier classification for conversion probability predictions
enum ConfidenceLevel: string
{
    case High     = 'high';
    case Moderate = 'moderate';
    case Low      = 'low';

    public static function fromScore(float $score): self
    {
        if ($score > 0.75) {
            return self::High;
        }

        if ($score >= 0.45) {
            return self::Moderate;
        }

        return self::Low;
    }

    public function label(): string
    {
        return match ($this) {
            self::High     => 'High Confidence',
            self::Moderate => 'Moderate Confidence',
            self::Low      => 'Low Confidence',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::High     => 'bg-green-100 text-green-800',
            self::Moderate => 'bg-yellow-100 text-yellow-800',
            self::Low      => 'bg-red-100 text-red-800',
        };
    }

    public function ringClass(): string
    {
        return match ($this) {
            self::High     => 'text-green-600',
            self::Moderate => 'text-yellow-600',
            self::Low      => 'text-red-500',
        };
    }
}
