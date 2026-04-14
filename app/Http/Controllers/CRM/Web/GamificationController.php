<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Enums\CRM\PeriodType;
use App\Http\Controllers\Controller;
use App\Services\CRM\GamificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * BRD: CRM-EC-010 — Gamification dashboard controller (web)
 */
class GamificationController extends Controller
{
    public function __construct(
        private readonly GamificationService $gamificationService
    ) {}

    /**
     * Display gamification dashboard
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $institutionId = $user->institution_id;
        $campusId = $user->campus_id;

        // Get period type from request (default: monthly)
        $periodType = PeriodType::tryFrom($request->get('period', 'monthly')) ?? PeriodType::MONTHLY;

        // Get counsellor's current score
        $currentScore = $this->gamificationService->getCounsellorScore($user->id, $periodType);

        // Get leaderboard
        $leaderboard = $this->gamificationService->getLeaderboard($institutionId, $campusId, $periodType, 50);

        // Get top performers
        $topPerformers = $this->gamificationService->getTopPerformers($institutionId, $campusId, $periodType, 10);

        // Get counsellor's badges
        $badges = $this->gamificationService->getCounsellorBadges($user->id);

        // Get counsellor's rank from leaderboard
        $counsellorRank = $leaderboard->search(fn($item) => $item->user_id === $user->id) + 1;

        return view('crm.gamification.index', [
            'currentScore' => $currentScore,
            'leaderboard' => $leaderboard,
            'topPerformers' => $topPerformers,
            'badges' => $badges,
            'counsellorRank' => $counsellorRank,
            'periodType' => $periodType,
        ]);
    }
}
