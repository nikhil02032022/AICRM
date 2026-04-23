<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use Illuminate\Support\Facades\DB;

// BRD: CRM-AR-004 — Admissions funnel visualisation: stage-wise lead counts, conversion rates, drop-off
final class FunnelAnalyticsService
{
    /**
     * Returns ordered funnel stages with counts, conversion rates, and drop-off.
     *
     * Funnel is cumulative: each stage counts leads that reached that stage OR progressed beyond it.
     * Stages map directly to the LeadStatus lifecycle (BRD: CRM-LC-001).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from?: string, to?: string} $filters
     * @return list<array{stage: string, label: string, count: int, conversion_rate: float, drop_off_count: int, drop_off_rate: float}>
     */
    public function getFunnelStages(array $scope, array $filters): array
    {
        $from          = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to            = $filters['to']   ?? now()->toDateString();
        $institutionId = $scope['institution_id'];

        $row = DB::table('leads')
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->selectRaw("
                COUNT(*) as total_leads,
                SUM(CASE WHEN status NOT IN ('new_enquiry','lost') THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN status IN ('counselling_done','application_started','application_submitted','offer_issued','fee_paid','enrolled','deferred') THEN 1 ELSE 0 END) as counselled,
                SUM(CASE WHEN status IN ('application_submitted','offer_issued','fee_paid','enrolled','deferred') THEN 1 ELSE 0 END) as applied,
                SUM(CASE WHEN status IN ('offer_issued','fee_paid','enrolled') THEN 1 ELSE 0 END) as offered,
                SUM(CASE WHEN status IN ('fee_paid','enrolled') THEN 1 ELSE 0 END) as fee_paid,
                SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as enrolled
            ")
            ->first();

        $rawStages = [
            ['stage' => 'enquiry',   'label' => 'Enquiries',       'count' => (int) ($row->total_leads ?? 0)],
            ['stage' => 'contacted', 'label' => 'Contacted',        'count' => (int) ($row->contacted   ?? 0)],
            ['stage' => 'counselled','label' => 'Counselled',       'count' => (int) ($row->counselled  ?? 0)],
            ['stage' => 'applied',   'label' => 'Applied',          'count' => (int) ($row->applied     ?? 0)],
            ['stage' => 'offered',   'label' => 'Offer Issued',     'count' => (int) ($row->offered     ?? 0)],
            ['stage' => 'fee_paid',  'label' => 'Fee Paid',         'count' => (int) ($row->fee_paid    ?? 0)],
            ['stage' => 'enrolled',  'label' => 'Enrolled',         'count' => (int) ($row->enrolled    ?? 0)],
        ];

        $stages = [];
        foreach ($rawStages as $i => $s) {
            $prevCount      = $i > 0 ? $rawStages[$i - 1]['count'] : $s['count'];
            $conversionRate = $prevCount > 0 ? round(($s['count'] / $prevCount) * 100, 1) : 0.0;
            $dropOffCount   = max(0, $prevCount - $s['count']);
            $dropOffRate    = $prevCount > 0 ? round(($dropOffCount / $prevCount) * 100, 1) : 0.0;

            $stages[] = [
                'stage'           => $s['stage'],
                'label'           => $s['label'],
                'count'           => $s['count'],
                'conversion_rate' => $i === 0 ? 100.0 : $conversionRate,
                'drop_off_count'  => $i === 0 ? 0 : $dropOffCount,
                'drop_off_rate'   => $i === 0 ? 0.0 : $dropOffRate,
            ];
        }

        return $stages;
    }

    /**
     * Per-source funnel summary: enquiries and enrolments per channel for the period.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array{from?: string, to?: string} $filters
     */
    public function getFunnelBySource(array $scope, array $filters): \Illuminate\Support\Collection
    {
        $from          = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to            = $filters['to']   ?? now()->toDateString();
        $institutionId = $scope['institution_id'];

        return DB::table('leads')
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->when($scope['campus_id'], fn ($q) => $q->where('campus_id', $scope['campus_id']))
            ->when($scope['counsellor_ids'], fn ($q) => $q->whereIn('assigned_counsellor_id', $scope['counsellor_ids']))
            ->select(
                'source',
                DB::raw('COUNT(*) as total_leads'),
                DB::raw("SUM(CASE WHEN status IN ('application_submitted','offer_issued','fee_paid','enrolled','deferred') THEN 1 ELSE 0 END) as total_applied"),
                DB::raw("SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as total_enrolled"),
            )
            ->groupBy('source')
            ->orderByDesc('total_leads')
            ->get();
    }
}
