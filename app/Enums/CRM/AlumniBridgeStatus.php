<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-EI-008 — Alumni bridge handoff status
enum AlumniBridgeStatus: string
{
    case PENDING   = 'pending';
    case TRIGGERED = 'triggered';
    case SUCCESS   = 'success';
    case FAILED    = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Pending',
            self::TRIGGERED => 'Triggered',
            self::SUCCESS   => 'Bridged',
            self::FAILED    => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING   => 'gray',
            self::TRIGGERED => 'blue',
            self::SUCCESS   => 'green',
            self::FAILED    => 'red',
        };
    }
}
