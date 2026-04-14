<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Enums\CRM\PeriodType;
use App\Services\CRM\GamificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * BRD: CRM-EC-010 — Job to update leaderboard rankings
 */
class UpdateLeaderboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    public function __construct(
        public readonly int $institutionId,
        public readonly ?int $campusId = null,
        public readonly PeriodType $periodType = PeriodType::MONTHLY
    ) {
        $this->onQueue('crm-gamification');
    }

    public function handle(GamificationService $service): void
    {
        $service->updateLeaderboard(
            $this->institutionId,
            $this->campusId,
            $this->periodType
        );
    }

    /**
     * Unique job identifier for preventing duplicates
     */
    public function uniqueId(): string
    {
        return sprintf(
            'leaderboard:%d:%s:%s',
            $this->institutionId,
            $this->campusId ?? 'all',
            $this->periodType->value
        );
    }
}
