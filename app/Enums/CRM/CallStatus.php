<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-016 — Call lifecycle statuses
enum CallStatus: string
{
    case INITIATED   = 'INITIATED';
    case RINGING     = 'RINGING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED   = 'COMPLETED';
    case FAILED      = 'FAILED';
    case NO_ANSWER   = 'NO_ANSWER';

    public function label(): string
    {
        return match($this) {
            self::INITIATED   => 'Initiated',
            self::RINGING     => 'Ringing',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED   => 'Completed',
            self::FAILED      => 'Failed',
            self::NO_ANSWER   => 'No Answer',
        };
    }

    public function colour(): string
    {
        return match($this) {
            self::INITIATED   => 'blue',
            self::RINGING     => 'yellow',
            self::IN_PROGRESS => 'indigo',
            self::COMPLETED   => 'green',
            self::FAILED      => 'red',
            self::NO_ANSWER   => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::NO_ANSWER], strict: true);
    }
}
