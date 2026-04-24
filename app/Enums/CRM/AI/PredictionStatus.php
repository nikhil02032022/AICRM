<?php

declare(strict_types=1);

namespace App\Enums\CRM\AI;

// BRD: CRM-AI-001 — Lifecycle states for Claude API conversion probability prediction
enum PredictionStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Processing => 'Processing',
            self::Completed  => 'Completed',
            self::Failed     => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed => true,
            default                       => false,
        };
    }
}
