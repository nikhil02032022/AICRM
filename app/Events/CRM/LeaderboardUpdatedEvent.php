<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Enums\CRM\PeriodType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BRD: CRM-EC-010 — Event fired when leaderboard is updated
 */
class LeaderboardUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $institutionId,
        public readonly ?int $campusId,
        public readonly PeriodType $periodType
    ) {}
}
