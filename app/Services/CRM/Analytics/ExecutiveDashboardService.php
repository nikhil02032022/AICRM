<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Models\CRM\Analytics\DashboardMetricSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-AR-006 — Director/management executive dashboard: KPI tiles with trend, 12-month trend, top programmes, campus breakdown
final class ExecutiveDashboardService
{
    /**
     * Six top-line KPI tiles with period-over-period trend comparison.
     *
     * Prior period = same duration immediately preceding the selected range.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from: string, to: string} $filters
     * @return array<string, array{label: string, value: int|float, prior: int|float, delta_pct: float|null, trend: string, is_currency?: bool, is_rate?: bool}>
     */
    public function getKpiTiles(array $scope, array $filters): array
    {
        $from = $filters['from'];
        $to   = $filters['to'];

        $days      = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $priorTo   = Carbon::parse($from)->subDay()->toDateString();
        $priorFrom = Carbon::parse($priorTo)->subDays($days - 1)->toDateString();

        $current = $this->fetchPeriodTotals($scope['institution_id'], $from, $to);
        $prior   = $this->fetchPeriodTotals($scope['institution_id'], $priorFrom, $priorTo);

        return [
            'leads'          => $this->buildTile('Total Leads',   $current['leads'],        $prior['leads']),
            'applications'   => $this->buildTile('Applications',  $current['applications'], $prior['applications']),
            'offers'         => $this->buildTile('Offers Issued', $current['offers'],       $prior['offers']),
            'enrolments'     => $this->buildTile('Enrolments',    $current['enrolments'],   $prior['enrolments']),
            'revenue'        => $this->buildTile('Revenue (₹)',   $current['revenue'],      $prior['revenue'], isCurrency: true),
            'enrolment_rate' => $this->buildRateTile($current['leads'], $current['enrolments'], $prior['leads'], $prior['enrolments']),
        ];
    }

    /**
     * 12-month trend series from dashboard_metric_snapshots (leads, enrolments, revenue per month).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     */
    public function getMonthlyTrend(array $scope): Collection
    {
        $since = now()->subMonths(11)->startOfMonth()->toDateString();

        $snapshots = DashboardMetricSnapshot::withoutGlobalScopes()
            ->where('institution_id', $scope['institution_id'])
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->whereIn('metric_key', ['leads_total', 'enrolments_total', 'revenue_total'])
            ->where('period_date', '>=', $since)
            ->orderBy('period_date')
            ->get(['period_date', 'metric_key', 'metric_value']);

        $byMonth = $snapshots
            ->groupBy(fn ($s) => substr($s->period_date, 0, 7));

        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        return $months->map(function (string $m) use ($byMonth): object {
            $entries = $byMonth->get($m, collect())->keyBy('metric_key');

            return (object) [
                'month'      => $m,
                'leads'      => (int) ($entries->get('leads_total')?->metric_value ?? 0),
                'enrolments' => (int) ($entries->get('enrolments_total')?->metric_value ?? 0),
                'revenue'    => (float) ($entries->get('revenue_total')?->metric_value ?? 0),
            ];
        });
    }

    /**
     * Top 5 programmes by confirmed enrolments in the selected period.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from: string, to: string} $filters
     */
    public function getTopProgrammes(array $scope, array $filters): Collection
    {
        return DB::table('leads as l')
            ->join('lead_programme_interests as lpi', 'lpi.lead_id', '=', 'l.id')
            ->join('crm_programmes as p', 'p.id', '=', 'lpi.crm_programme_id')
            ->where('l.institution_id', $scope['institution_id'])
            ->whereBetween('l.created_at', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('l.deleted_at')
            ->where('lpi.is_primary', true)
            ->select(
                'p.name as programme',
                'p.code',
                DB::raw('COUNT(l.id) as total_leads'),
                DB::raw("SUM(CASE WHEN lpi.status = 'enrolled' THEN 1 ELSE 0 END) as total_enrolments"),
            )
            ->groupBy('p.id', 'p.name', 'p.code')
            ->orderByDesc('total_enrolments')
            ->limit(5)
            ->get();
    }

    /**
     * Per-campus breakdown: leads, applications, enrolments.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from: string, to: string} $filters
     */
    public function getCampusBreakdown(array $scope, array $filters): Collection
    {
        return DB::table('leads as l')
            ->leftJoin('campuses as c', 'c.id', '=', 'l.campus_id')
            ->where('l.institution_id', $scope['institution_id'])
            ->whereBetween('l.created_at', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('l.deleted_at')
            ->select(
                DB::raw("COALESCE(c.name, 'Unassigned') as campus"),
                DB::raw('COUNT(l.id) as total_leads'),
                DB::raw("SUM(CASE WHEN l.status IN ('application_submitted','offer_issued','fee_paid','enrolled','deferred') THEN 1 ELSE 0 END) as total_applications"),
                DB::raw("SUM(CASE WHEN l.status = 'enrolled' THEN 1 ELSE 0 END) as total_enrolments"),
            )
            ->groupBy('l.campus_id', DB::raw("COALESCE(c.name, 'Unassigned')"))
            ->orderByDesc('total_leads')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return array{leads: int, applications: int, offers: int, enrolments: int, revenue: float} */
    private function fetchPeriodTotals(int $institutionId, string $from, string $to): array
    {
        $base = DB::table('leads')
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('deleted_at');

        $row = (clone $base)
            ->selectRaw("
                COUNT(*) as leads,
                SUM(CASE WHEN status IN ('application_submitted','offer_issued','fee_paid','enrolled','deferred') THEN 1 ELSE 0 END) as applications,
                SUM(CASE WHEN status IN ('offer_issued','fee_paid','enrolled') THEN 1 ELSE 0 END) as offers,
                SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as enrolments
            ")
            ->first();

        $revenue = (float) DB::table('payment_transactions')
            ->where('institution_id', $institutionId)
            ->where('status', 'success')
            ->whereBetween('confirmed_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->sum('amount');

        return [
            'leads'        => (int) ($row->leads        ?? 0),
            'applications' => (int) ($row->applications ?? 0),
            'offers'       => (int) ($row->offers       ?? 0),
            'enrolments'   => (int) ($row->enrolments   ?? 0),
            'revenue'      => $revenue,
        ];
    }

    /** @return array{label: string, value: int|float, prior: int|float, delta_pct: float|null, trend: string, is_currency: bool} */
    private function buildTile(string $label, int|float $current, int|float $prior, bool $isCurrency = false): array
    {
        $delta = $prior > 0 ? round((($current - $prior) / $prior) * 100, 1) : null;

        return [
            'label'       => $label,
            'value'       => $current,
            'prior'       => $prior,
            'delta_pct'   => $delta,
            'trend'       => $delta === null ? 'neutral' : ($delta >= 0 ? 'up' : 'down'),
            'is_currency' => $isCurrency,
            'is_rate'     => false,
        ];
    }

    /** @return array{label: string, value: float, prior: float, delta_pct: float|null, trend: string, is_rate: bool} */
    private function buildRateTile(int $leads, int $enrolments, int $priorLeads, int $priorEnrolments): array
    {
        $rate      = $leads > 0 ? round(($enrolments / $leads) * 100, 1) : 0.0;
        $priorRate = $priorLeads > 0 ? round(($priorEnrolments / $priorLeads) * 100, 1) : 0.0;
        $delta     = $priorRate > 0 ? round($rate - $priorRate, 1) : null;

        return [
            'label'       => 'Enrolment Rate',
            'value'       => $rate,
            'prior'       => $priorRate,
            'delta_pct'   => $delta,
            'trend'       => $delta === null ? 'neutral' : ($delta >= 0 ? 'up' : 'down'),
            'is_currency' => false,
            'is_rate'     => true,
        ];
    }
}
