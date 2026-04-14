<?php

declare(strict_types=1);

namespace App\Repositories\CRM;

use App\Enums\CRM\PeriodType;
use App\Models\CRM\Badge;
use App\Models\CRM\CounsellorBadge;
use App\Models\CRM\GamificationScore;
use App\Models\CRM\Leaderboard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * BRD: CRM-EC-010 — Gamification repository for data access
 */
class GamificationRepository
{
    /**
     * Get or create gamification score for a user in a period
     */
    public function getOrCreateScore(
        int $userId,
        int $institutionId,
        ?int $campusId,
        PeriodType $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): GamificationScore {
        return GamificationScore::firstOrCreate(
            [
                'user_id' => $userId,
                'period_type' => $periodType,
                'period_start' => $periodStart,
            ],
            [
                'institution_id' => $institutionId,
                'campus_id' => $campusId,
                'period_end' => $periodEnd,
            ]
        );
    }

    /**
     * Get score for a user in current period
     */
    public function getCurrentScore(int $userId, PeriodType $periodType): ?GamificationScore
    {
        $dates = $this->getPeriodDates($periodType);

        return GamificationScore::where('user_id', $userId)
            ->where('period_type', $periodType)
            ->where('period_start', $dates['start'])
            ->first();
    }

    /**
     * Update KPI metrics for a score
     */
    public function updateMetrics(GamificationScore $score, array $metrics): GamificationScore
    {
        $score->update($metrics);

        // Recalculate conversion rate
        if (isset($metrics['leads_handled']) || isset($metrics['leads_converted'])) {
            $score->conversion_rate = $score->calculateConversionRate();
            $score->save();
        }

        return $score->fresh();
    }

    /**
     * Increment a metric for a score
     */
    public function incrementMetric(GamificationScore $score, string $metric, int $amount = 1): GamificationScore
    {
        $score->increment($metric, $amount);
        
        // Recalculate conversion rate if needed
        if (in_array($metric, ['leads_handled', 'leads_converted'])) {
            $score->conversion_rate = $score->calculateConversionRate();
            $score->save();
        }

        return $score->fresh();
    }

    /**
     * Award points to a counsellor
     */
    public function awardPoints(GamificationScore $score, int $points): GamificationScore
    {
        return $this->incrementMetric($score, 'total_points', $points);
    }

    /**
     * Get top performers for a period
     */
    public function getTopPerformers(
        int $institutionId,
        ?int $campusId,
        PeriodType $periodType,
        Carbon $periodStart,
        int $limit = 10
    ): Collection {
        $query = GamificationScore::with('user')
            ->where('institution_id', $institutionId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->orderByDesc('total_points')
            ->orderByDesc('conversion_rate')
            ->limit($limit);

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        return $query->get();
    }

    /**
     * Get leaderboard for a period
     */
    public function getLeaderboard(
        int $institutionId,
        ?int $campusId,
        PeriodType $periodType,
        Carbon $periodStart,
        int $limit = 50
    ): Collection {
        $query = Leaderboard::with('user')
            ->where('institution_id', $institutionId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->orderBy('rank')
            ->limit($limit);

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        return $query->get();
    }

    /**
     * Update leaderboard rankings for a period
     */
    public function updateLeaderboardRankings(
        int $institutionId,
        ?int $campusId,
        PeriodType $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        // Get all scores for this period, ordered by total points and conversion rate
        $query = GamificationScore::where('institution_id', $institutionId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->orderByDesc('total_points')
            ->orderByDesc('conversion_rate')
            ->orderByDesc('leads_converted');

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        $scores = $query->get();

        // Get previous period leaderboard for rank change calculation
        $previousPeriodStart = $this->getPreviousPeriodStart($periodType, $periodStart);
        $previousLeaderboard = Leaderboard::where('institution_id', $institutionId)
            ->where('period_type', $periodType)
            ->where('period_start', $previousPeriodStart)
            ->get()
            ->keyBy('user_id');

        // Update or create leaderboard entries
        $rank = 1;
        foreach ($scores as $score) {
            $previousRank = $previousLeaderboard->get($score->user_id)?->rank ?? 0;
            $rankChange = $previousRank > 0 ? ($previousRank - $rank) : 0;

            $leaderboard = Leaderboard::updateOrCreate(
                [
                    'user_id' => $score->user_id,
                    'period_type' => $periodType,
                    'period_start' => $periodStart,
                ],
                [
                    'institution_id' => $institutionId,
                    'campus_id' => $campusId,
                    'rank' => $rank,
                    'total_points' => $score->total_points,
                    'conversion_rate' => $score->conversion_rate,
                    'leads_converted' => $score->leads_converted,
                    'period_end' => $periodEnd,
                    'rank_change' => $rankChange,
                    'trend' => $leaderboard->determineTrend() ?? 'stable',
                ]
            );

            $rank++;
        }
    }

    /**
     * Get all active badges
     */
    public function getActiveBadges(): Collection
    {
        return Badge::where('is_active', true)->get();
    }

    /**
     * Award a badge to a counsellor
     */
    public function awardBadge(
        int $userId,
        int $institutionId,
        int $badgeId,
        array $criteriaMet
    ): CounsellorBadge {
        $badge = Badge::findOrFail($badgeId);

        return CounsellorBadge::firstOrCreate(
            [
                'user_id' => $userId,
                'badge_id' => $badgeId,
            ],
            [
                'institution_id' => $institutionId,
                'points_earned' => $badge->points,
                'earned_at' => now(),
                'criteria_met' => $criteriaMet,
            ]
        );
    }

    /**
     * Get badges earned by a counsellor
     */
    public function getCounsellorBadges(int $userId): Collection
    {
        return CounsellorBadge::with('badge')
            ->where('user_id', $userId)
            ->orderByDesc('earned_at')
            ->get();
    }

    /**
     * Check and award eligible badges to a counsellor
     */
    public function checkAndAwardBadges(int $userId, int $institutionId, array $metrics): Collection
    {
        $activeBadges = $this->getActiveBadges();
        $awardedBadges = collect();

        foreach ($activeBadges as $badge) {
            // Skip if already earned
            if (CounsellorBadge::where('user_id', $userId)->where('badge_id', $badge->id)->exists()) {
                continue;
            }

            // Check if criteria is met
            if ($badge->isCriteriaMet($metrics)) {
                $awardedBadge = $this->awardBadge($userId, $institutionId, $badge->id, $metrics);
                $awardedBadges->push($awardedBadge);
            }
        }

        return $awardedBadges;
    }

    /**
     * Get period start and end dates for current period
     */
    public function getPeriodDates(PeriodType $periodType): array
    {
        $now = now();

        return match ($periodType) {
            PeriodType::DAILY => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            PeriodType::WEEKLY => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            PeriodType::MONTHLY => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            PeriodType::QUARTERLY => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            PeriodType::YEARLY => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
        };
    }

    /**
     * Get previous period start date
     */
    private function getPreviousPeriodStart(PeriodType $periodType, Carbon $currentStart): Carbon
    {
        return match ($periodType) {
            PeriodType::DAILY => $currentStart->copy()->subDay(),
            PeriodType::WEEKLY => $currentStart->copy()->subWeek(),
            PeriodType::MONTHLY => $currentStart->copy()->subMonth(),
            PeriodType::QUARTERLY => $currentStart->copy()->subQuarter(),
            PeriodType::YEARLY => $currentStart->copy()->subYear(),
        };
    }
}
