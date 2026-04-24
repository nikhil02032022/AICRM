<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Analytics;

use App\Http\Controllers\Controller;
use App\Services\CRM\Alumni\AlumniNpsService;
use App\Services\CRM\Analytics\CounsellorDashboardService;
use App\Services\CRM\Analytics\DashboardScopeService;
use App\Services\CRM\Analytics\InstitutionDashboardService;
use App\Services\CRM\Analytics\FunnelAnalyticsService;
use App\Services\CRM\Analytics\MarketingDashboardService;
use App\Services\CRM\Analytics\ExecutiveDashboardService;
use App\Services\CRM\Analytics\SeatAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AR-001 to CRM-AR-006 — Analytics dashboard web controllers
// BRD: CRM-AR-005 seat availability added
final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardScopeService       $scopeService,
        private readonly InstitutionDashboardService $institutionService,
        private readonly CounsellorDashboardService  $counsellorService,
        private readonly MarketingDashboardService   $marketingService,
        private readonly FunnelAnalyticsService      $funnelService,
        private readonly SeatAvailabilityService     $seatService,
        private readonly ExecutiveDashboardService   $executiveService,
        // BRD: CRM-AL-004 — NPS trend for executive dashboard sparkline
        private readonly AlumniNpsService            $alumniNpsService,
    ) {}

    // BRD: CRM-AR-001 — Institution admissions dashboard: leads, applications, offers, enrolments, revenue
    public function institutionDashboard(Request $request): \Illuminate\View\View
    {
        Gate::authorize('crm.analytics.institution');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from' => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'   => $request->input('to', now()->toDateString()),
        ];

        $cachePrefix = "dashboard:inst:{$scope['institution_id']}";
        $filterKey   = md5(serialize($filters));

        // NFR-P-001: 5-minute cache on expensive KPI aggregation queries
        $kpis       = Cache::remember("{$cachePrefix}:kpis:{$filterKey}", 300, fn () => $this->institutionService->getSummaryKpis($scope, $filters));
        $byProgramme = Cache::remember("{$cachePrefix}:by_prog:{$filterKey}", 300, fn () => $this->institutionService->getByProgramme($scope, $filters));
        $bySource    = Cache::remember("{$cachePrefix}:by_src:{$filterKey}", 300, fn () => $this->institutionService->getBySource($scope, $filters));
        $trend       = Cache::remember("{$cachePrefix}:trend", 300, fn () => $this->institutionService->getMonthlyTrend($scope));

        return view('crm.analytics.dashboards.institution', compact(
            'kpis', 'byProgramme', 'bySource', 'trend', 'filters',
        ));
    }

    // BRD: CRM-AR-002 — Counsellor performance dashboard
    public function counsellorDashboard(Request $request): \Illuminate\View\View
    {
        Gate::authorize('crm.analytics.view');

        $user  = $request->user();
        $scope = $this->scopeService->resolveScope($user);

        $filters = [
            'from' => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'   => $request->input('to', now()->toDateString()),
        ];

        $isManager   = $scope['role'] !== 'counsellor';
        $ownKpis     = $this->counsellorService->getOwnKpis($user->id, $scope['institution_id'], $filters);
        $teamGrid    = $isManager
            ? $this->counsellorService->getPerformanceGrid($scope, $filters)
            : collect();

        return view('crm.analytics.dashboards.counsellor', compact(
            'ownKpis', 'teamGrid', 'isManager', 'filters',
        ));
    }

    // BRD: CRM-AR-003 — Marketing campaign dashboard: spend vs leads, CPL, CPE, channel ROI
    public function marketingDashboard(Request $request): \Illuminate\View\View
    {
        Gate::authorize('crm.analytics.marketing');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from' => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'   => $request->input('to', now()->toDateString()),
        ];

        $kpis      = $this->marketingService->getSummaryKpis($scope, $filters);
        $byChannel = $this->marketingService->getByChannel($scope, $filters);
        $trend     = $this->marketingService->getMonthlyTrend($scope);

        return view('crm.analytics.dashboards.marketing', compact(
            'kpis', 'byChannel', 'trend', 'filters',
        ));
    }

    // BRD: CRM-AR-006 — Executive dashboard: institution-wide KPI tiles with trend, 12-month series, top programmes, campus breakdown
    public function executiveDashboard(Request $request): \Illuminate\View\View
    {
        Gate::authorize('crm.analytics.executive');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from' => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'   => $request->input('to', now()->toDateString()),
        ];

        $cachePrefix = "dashboard:exec:{$scope['institution_id']}";
        $filterKey   = md5(serialize($filters));

        // NFR-P-001: 5-minute cache on executive KPI aggregations
        $kpis            = Cache::remember("{$cachePrefix}:kpis:{$filterKey}", 300, fn () => $this->executiveService->getKpiTiles($scope, $filters));
        $trend           = Cache::remember("{$cachePrefix}:trend", 300, fn () => $this->executiveService->getMonthlyTrend($scope));
        $topProgrammes   = Cache::remember("{$cachePrefix}:top_prog:{$filterKey}", 300, fn () => $this->executiveService->getTopProgrammes($scope, $filters));
        $campusBreakdown = Cache::remember("{$cachePrefix}:campus:{$filterKey}", 300, fn () => $this->executiveService->getCampusBreakdown($scope, $filters));

        // BRD: CRM-AL-004 — NPS data for alumni NPS card on executive dashboard
        $npsLatest = $this->alumniNpsService->getLatestScore($scope['institution_id']);
        $npsTrend  = $this->alumniNpsService->getTrend($scope['institution_id'], 12);

        return view('crm.analytics.dashboards.executive', compact(
            'kpis', 'trend', 'topProgrammes', 'campusBreakdown', 'filters',
            'npsLatest', 'npsTrend',
        ));
    }

    // BRD: CRM-AR-005 — Seat availability vs confirmed enrolments: real-time per-programme view
    public function seatAvailability(Request $request): \Illuminate\View\View
    {
        Gate::authorize('crm.analytics.view');

        $scope       = $this->scopeService->resolveScope($request->user());
        $kpis        = $this->seatService->getSummaryKpis($scope);
        $programmes  = $this->seatService->getProgrammeSeatData($scope);

        return view('crm.analytics.dashboards.seat-availability', compact('kpis', 'programmes'));
    }

    // BRD: CRM-AR-004 — Admissions funnel: stage-wise conversion and drop-off visualisation
    public function funnelDashboard(Request $request): \Illuminate\View\View
    {
        Gate::authorize('crm.analytics.view');

        $scope = $this->scopeService->resolveScope($request->user());

        $filters = [
            'from' => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'   => $request->input('to', now()->toDateString()),
        ];

        $cachePrefix = "dashboard:funnel:{$scope['institution_id']}";
        $filterKey   = md5(serialize($filters));

        // NFR-P-001: 5-minute cache on funnel aggregation queries
        $stages    = Cache::remember("{$cachePrefix}:stages:{$filterKey}", 300, fn () => $this->funnelService->getFunnelStages($scope, $filters));
        $bySource  = Cache::remember("{$cachePrefix}:by_src:{$filterKey}", 300, fn () => $this->funnelService->getFunnelBySource($scope, $filters));

        return view('crm.analytics.dashboards.funnel', compact(
            'stages', 'bySource', 'filters',
        ));
    }
}
