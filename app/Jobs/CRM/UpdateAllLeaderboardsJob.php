<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Enums\CRM\PeriodType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * BRD: CRM-EC-010 — Job to update all institution leaderboards
 * Scheduled to run daily
 */
class UpdateAllLeaderboardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour

    public function __construct()
    {
        $this->onQueue('crm-gamification');
    }

    public function handle(): void
    {
        // TODO: When Institution model is available, iterate and dispatch per institution
        // For now, this job is a placeholder for scheduled leaderboard updates
        // Manual trigger: UpdateLeaderboardJob::dispatch($institutionId, $campusId, $periodType);
    }
}
