<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Enums\CRM\PeriodType;
use App\Events\CRM\BadgeEarnedEvent;
use App\Events\CRM\LeaderboardUpdatedEvent;
use App\Events\CRM\ScoreUpdatedEvent;
use App\Models\CRM\GamificationScore;
use App\Repositories\CRM\GamificationRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * BRD: CRM-EC-010 — Gamification service for counsellor performance tracking
 */
class GamificationService
{
    public function __construct(
        private readonly GamificationRepository $repository
    ) {}

    /**
     * Record lead handled by counsellor
     */
    public function recordLeadHandled(
        int $userId,
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        $score = $this->repository->incrementMetric($score, 'leads_handled');
        $this->repository->awardPoints($score, 5); // 5 points per lead handled

        $this->updateStreakAndActivity($score);
        $this->checkAndAwardBadges($userId, $institutionId, $score);

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Record lead conversion by counsellor
     */
    public function recordLeadConversion(
        int $userId,
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        $score = $this->repository->incrementMetric($score, 'leads_converted');
        $this->repository->awardPoints($score, 50); // 50 points per conversion

        $this->updateStreakAndActivity($score);
        $this->checkAndAwardBadges($userId, $institutionId, $score);

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Record call made by counsellor
     */
    public function recordCallMade(
        int $userId,
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        $score = $this->repository->incrementMetric($score, 'calls_made');
        $this->repository->awardPoints($score, 2); // 2 points per call

        $this->updateStreakAndActivity($score);

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Record email sent by counsellor
     */
    public function recordEmailSent(
        int $userId,
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        $score = $this->repository->incrementMetric($score, 'emails_sent');
        $this->repository->awardPoints($score, 1); // 1 point per email

        $this->updateStreakAndActivity($score);

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Record meeting scheduled by counsellor
     */
    public function recordMeetingScheduled(
        int $userId,
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        $score = $this->repository->incrementMetric($score, 'meetings_scheduled');
        $this->repository->awardPoints($score, 10); // 10 points per meeting

        $this->updateStreakAndActivity($score);

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Record application submitted
     */
    public function recordApplicationSubmitted(
        int $userId,
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        $score = $this->repository->incrementMetric($score, 'applications_submitted');
        $this->repository->awardPoints($score, 25); // 25 points per application

        $this->updateStreakAndActivity($score);
        $this->checkAndAwardBadges($userId, $institutionId, $score);

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Update response time for counsellor
     */
    public function updateResponseTime(
        int $userId,
        int $institutionId,
        int $responseTimeMinutes,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        // Calculate running average
        $currentAvg = $score->avg_response_time_minutes;
        $totalRecords = $score->leads_handled > 0 ? $score->leads_handled : 1;
        $newAvg = (int) (($currentAvg * ($totalRecords - 1) + $responseTimeMinutes) / $totalRecords);

        $score = $this->repository->updateMetrics($score, [
            'avg_response_time_minutes' => $newAvg,
        ]);

        // Award points for fast response (under 15 minutes)
        if ($responseTimeMinutes <= 15) {
            $this->repository->awardPoints($score, 10);
        }

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Update student satisfaction score
     */
    public function updateSatisfactionScore(
        int $userId,
        int $institutionId,
        float $satisfactionScore,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): GamificationScore {
        $dates = $this->repository->getPeriodDates($periodType);
        $score = $this->repository->getOrCreateScore(
            $userId,
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        // Calculate running average
        $currentAvg = $score->student_satisfaction_score;
        $totalRecords = $score->leads_converted > 0 ? $score->leads_converted : 1;
        $newAvg = round(($currentAvg * ($totalRecords - 1) + $satisfactionScore) / $totalRecords, 2);

        $score = $this->repository->updateMetrics($score, [
            'student_satisfaction_score' => $newAvg,
        ]);

        // Award points for high satisfaction (4.5+)
        if ($satisfactionScore >= 4.5) {
            $this->repository->awardPoints($score, 20);
        }

        event(new ScoreUpdatedEvent($score));

        return $score;
    }

    /**
     * Get counsellor's current score
     */
    public function getCounsellorScore(
        int $userId,
        PeriodType $periodType = PeriodType::MONTHLY
    ): ?GamificationScore {
        return $this->repository->getCurrentScore($userId, $periodType);
    }

    /**
     * Get leaderboard for institution
     */
    public function getLeaderboard(
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY,
        int $limit = 50
    ): Collection {
        $dates = $this->repository->getPeriodDates($periodType);

        return $this->repository->getLeaderboard(
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $limit
        );
    }

    /**
     * Get top performers
     */
    public function getTopPerformers(
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY,
        int $limit = 10
    ): Collection {
        $dates = $this->repository->getPeriodDates($periodType);

        return $this->repository->getTopPerformers(
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $limit
        );
    }

    /**
     * Update leaderboard rankings
     */
    public function updateLeaderboard(
        int $institutionId,
        ?int $campusId = null,
        PeriodType $periodType = PeriodType::MONTHLY
    ): void {
        $dates = $this->repository->getPeriodDates($periodType);

        $this->repository->updateLeaderboardRankings(
            $institutionId,
            $campusId,
            $periodType,
            $dates['start'],
            $dates['end']
        );

        event(new LeaderboardUpdatedEvent($institutionId, $campusId, $periodType));
    }

    /**
     * Get counsellor's badges
     */
    public function getCounsellorBadges(int $userId): Collection
    {
        return $this->repository->getCounsellorBadges($userId);
    }

    /**
     * Update streak and last activity date
     */
    private function updateStreakAndActivity(GamificationScore $score): void
    {
        $today = now()->startOfDay();
        $lastActivity = $score->last_activity_date ? Carbon::parse($score->last_activity_date)->startOfDay() : null;

        if ($lastActivity === null || $lastActivity->isBefore($today)) {
            // Check if streak should continue or reset
            if ($lastActivity && $lastActivity->copy()->addDay()->isSameDay($today)) {
                // Consecutive day - increment streak
                $this->repository->incrementMetric($score, 'streak_days');
            } elseif ($lastActivity && !$lastActivity->isSameDay($today)) {
                // Streak broken - reset to 1
                $this->repository->updateMetrics($score, ['streak_days' => 1]);
            } else {
                // First activity
                $this->repository->updateMetrics($score, ['streak_days' => 1]);
            }

            $this->repository->updateMetrics($score, ['last_activity_date' => $today]);
        }
    }

    /**
     * Check and award eligible badges
     */
    private function checkAndAwardBadges(int $userId, int $institutionId, GamificationScore $score): void
    {
        $metrics = [
            'leads_handled' => $score->leads_handled,
            'leads_converted' => $score->leads_converted,
            'conversion_rate' => (float) $score->conversion_rate,
            'calls_made' => $score->calls_made,
            'emails_sent' => $score->emails_sent,
            'meetings_scheduled' => $score->meetings_scheduled,
            'applications_submitted' => $score->applications_submitted,
            'total_points' => $score->total_points,
            'streak_days' => $score->streak_days,
            'student_satisfaction_score' => (float) $score->student_satisfaction_score,
        ];

        $awardedBadges = $this->repository->checkAndAwardBadges($userId, $institutionId, $metrics);

        foreach ($awardedBadges as $badge) {
            // Award bonus points
            $this->repository->awardPoints($score, $badge->points_earned);

            event(new BadgeEarnedEvent($badge));
        }
    }
}
