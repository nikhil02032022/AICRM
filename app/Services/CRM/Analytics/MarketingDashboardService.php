<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-AR-003 — Marketing campaign dashboard: spend vs leads, CPL, CPE, channel ROI
final class MarketingDashboardService
{
    /**
     * Summary KPI tiles: total spend, total leads, total enrolments, CPL, CPE.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from?: string, to?: string} $filters
     * @return array{total_spend: float, total_leads: int, total_enrolments: int, total_revenue: float, cpl: float, cpe: float}
     */
    public function getSummaryKpis(array $scope, array $filters): array
    {
        $from          = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to            = $filters['to']   ?? now()->toDateString();
        $institutionId = $scope['institution_id'];

        $totalSpend = (float) DB::table('campaign_spends')
            ->where('institution_id', $institutionId)
            ->where('period_start', '>=', $from)
            ->where('period_end', '<=', $to)
            ->whereNull('deleted_at')
            ->sum('amount');

        $totalLeads = (int) DB::table('leads')
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->count();

        $totalEnrolments = (int) DB::table('leads')
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereIn('status', ['enrolled', 'converted'])
            ->whereNull('deleted_at')
            ->count();

        $totalRevenue = (float) DB::table('payment_transactions as pt')
            ->join('leads as l', 'l.uuid', '=', 'pt.lead_uuid')
            ->where('pt.institution_id', $institutionId)
            ->where('pt.status', 'success')
            ->whereBetween('pt.confirmed_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('pt.deleted_at')
            ->sum('pt.amount');

        return [
            'total_spend'      => $totalSpend,
            'total_leads'      => $totalLeads,
            'total_enrolments' => $totalEnrolments,
            'total_revenue'    => $totalRevenue,
            'cpl'              => $totalLeads > 0 ? round($totalSpend / $totalLeads, 2) : 0.0,
            'cpe'              => $totalEnrolments > 0 ? round($totalSpend / $totalEnrolments, 2) : 0.0,
        ];
    }

    /**
     * Per-channel breakdown: spend, leads, enrolments, CPL, CPE, ROI.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from?: string, to?: string} $filters
     */
    public function getByChannel(array $scope, array $filters): Collection
    {
        $from          = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to            = $filters['to']   ?? now()->toDateString();
        $institutionId = $scope['institution_id'];

        $spendByChannel = DB::table('campaign_spends')
            ->where('institution_id', $institutionId)
            ->where('period_start', '>=', $from)
            ->where('period_end', '<=', $to)
            ->whereNull('deleted_at')
            ->select('source', DB::raw('SUM(amount) as total_spend'))
            ->groupBy('source')
            ->get()
            ->keyBy('source');

        $leadsByChannel = DB::table('leads')
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->select(
                'source',
                DB::raw('COUNT(*) as total_leads'),
                DB::raw("SUM(CASE WHEN status IN ('enrolled','converted') THEN 1 ELSE 0 END) as total_enrolments"),
            )
            ->groupBy('source')
            ->get()
            ->keyBy('source');

        $revenueByChannel = DB::table('payment_transactions as pt')
            ->join('leads as l', 'l.uuid', '=', 'pt.lead_uuid')
            ->where('pt.institution_id', $institutionId)
            ->where('pt.status', 'success')
            ->whereBetween('pt.confirmed_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('pt.deleted_at')
            ->select('l.source', DB::raw('SUM(pt.amount) as total_revenue'))
            ->groupBy('l.source')
            ->get()
            ->keyBy('source');

        $allSources = $spendByChannel->keys()
            ->merge($leadsByChannel->keys())
            ->unique();

        return $allSources->map(function ($source) use ($spendByChannel, $leadsByChannel, $revenueByChannel) {
            $spend      = (float) ($spendByChannel->get($source)?->total_spend ?? 0);
            $leads      = (int) ($leadsByChannel->get($source)?->total_leads ?? 0);
            $enrolments = (int) ($leadsByChannel->get($source)?->total_enrolments ?? 0);
            $revenue    = (float) ($revenueByChannel->get($source)?->total_revenue ?? 0);

            return (object) [
                'source'           => $source,
                'total_spend'      => $spend,
                'total_leads'      => $leads,
                'total_enrolments' => $enrolments,
                'total_revenue'    => $revenue,
                'cpl'              => $leads > 0 ? round($spend / $leads, 2) : 0.0,
                'cpe'              => $enrolments > 0 ? round($spend / $enrolments, 2) : 0.0,
                'roi'              => $spend > 0 ? round((($revenue - $spend) / $spend) * 100, 1) : null,
            ];
        })->sortByDesc('total_leads')->values();
    }

    /**
     * Monthly spend vs leads trend for the last 12 months (for Chart.js line chart).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     */
    public function getMonthlyTrend(array $scope): Collection
    {
        $institutionId = $scope['institution_id'];
        $since         = now()->subMonths(11)->startOfMonth()->toDateString();

        $spendTrend = DB::table('campaign_spends')
            ->where('institution_id', $institutionId)
            ->where('period_start', '>=', $since)
            ->whereNull('deleted_at')
            ->select([
                DB::raw("DATE_FORMAT(period_start, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total_spend'),
            ])
            ->groupBy(DB::raw("DATE_FORMAT(period_start, '%Y-%m')"))
            ->get()
            ->keyBy('month');

        $leadTrend = DB::table('leads')
            ->where('institution_id', $institutionId)
            ->where('created_at', '>=', $since . ' 00:00:00')
            ->whereNull('deleted_at')
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total_leads'),
            ])
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->get()
            ->keyBy('month');

        // Build a full 12-month series so the chart has no gaps
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        return $months->map(fn ($m) => (object) [
            'month'       => $m,
            'total_spend' => (float) ($spendTrend->get($m)?->total_spend ?? 0),
            'total_leads' => (int) ($leadTrend->get($m)?->total_leads ?? 0),
        ]);
    }
}
