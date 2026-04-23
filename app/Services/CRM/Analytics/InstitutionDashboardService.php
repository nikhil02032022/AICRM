<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Models\CRM\Analytics\DashboardMetricSnapshot;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-AR-001 — Institution admissions dashboard: leads, applications, offers, enrolments, revenue by programme/campus/source/period
final class InstitutionDashboardService
{
    public function __construct(
        private readonly DashboardScopeService $scopeService,
    ) {}

    /**
     * Summary KPI tiles for the institution dashboard.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from?: string, to?: string} $filters
     * @return array{total_leads: int, total_applications: int, total_offers: int, total_enrolments: int, total_revenue: float}
     */
    public function getSummaryKpis(array $scope, array $filters): array
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        $leadQ = Lead::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->whereBetween('created_at', [$from, $to]);

        $appQ = Application::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->whereBetween('created_at', [$from, $to]);

        if ($scope['campus_id']) {
            $appQ->where('campus_id', $scope['campus_id']);
        }

        if ($scope['counsellor_ids']) {
            $leadQ->whereIn('assigned_counsellor_id', $scope['counsellor_ids']);
            $appQ->whereIn('assigned_counsellor_id', $scope['counsellor_ids']);
        }

        $revenue = PaymentTransaction::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->where('status', 'success')
            ->whereBetween('confirmed_at', [$from, $to])
            ->sum('amount');

        return [
            'total_leads'        => $leadQ->count(),
            'total_applications' => (clone $appQ)->count(),
            'total_offers'       => (clone $appQ)->whereIn('status', ['offer_issued', 'offer_accepted'])->count(),
            'total_enrolments'   => (clone $appQ)->where('status', 'enrolled')->count(),
            'total_revenue'      => (float) $revenue,
        ];
    }

    /**
     * Applications grouped by programme.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from?: string, to?: string} $filters
     */
    public function getByProgramme(array $scope, array $filters): Collection
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        return DB::table('applications as a')
            ->join('crm_programmes as p', 'a.programme_id', '=', 'p.id')
            ->where('a.institution_id', $scope['institution_id'])
            ->when($scope['campus_id'], fn ($q) => $q->where('a.campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('a.assigned_counsellor_id', $scope['counsellor_ids']))
            ->whereBetween('a.created_at', [$from, $to])
            ->whereNull('a.deleted_at')
            ->select([
                'p.id as programme_id',
                'p.name as programme',
                DB::raw('COUNT(*) as total_applications'),
                DB::raw("SUM(CASE WHEN a.status = 'enrolled' THEN 1 ELSE 0 END) as total_enrolments"),
                DB::raw("SUM(CASE WHEN a.status IN ('offer_issued','offer_accepted') THEN 1 ELSE 0 END) as total_offers"),
            ])
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('total_applications')
            ->get();
    }

    /**
     * Leads grouped by source.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from?: string, to?: string} $filters
     */
    public function getBySource(array $scope, array $filters): Collection
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        return DB::table('leads')
            ->where('institution_id', $scope['institution_id'])
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at')
            ->select([
                'source',
                DB::raw('COUNT(*) as total_leads'),
            ])
            ->groupBy('source')
            ->orderByDesc('total_leads')
            ->get();
    }

    /**
     * Monthly trend for leads and applications (last 12 months).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     */
    public function getMonthlyTrend(array $scope): Collection
    {
        return DashboardMetricSnapshot::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->whereIn('metric_key', ['leads_total', 'applications_total', 'enrolments_total', 'revenue_total'])
            ->where('period_date', '>=', now()->subMonths(12)->startOfMonth()->toDateString())
            ->orderBy('period_date')
            ->get(['period_date', 'metric_key', 'metric_value']);
    }
}
