<?php

declare(strict_types=1);

namespace App\Enums\CRM\AI;

// BRD: CRM-AI-007 — Lifecycle states for Claude API call transcription and summary
enum TranscriptionStatus: string
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

    public function colour(): string
    {
        return match ($this) {
            self::Pending    => 'yellow',
            self::Processing => 'blue',
            self::Completed  => 'green',
            self::Failed     => 'red',
        };
    }
}
