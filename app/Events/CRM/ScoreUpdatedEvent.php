<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\GamificationScore;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BRD: CRM-EC-010 — Event fired when a counsellor's gamification score is updated
 */
class ScoreUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly GamificationScore $score
    ) {}
}
