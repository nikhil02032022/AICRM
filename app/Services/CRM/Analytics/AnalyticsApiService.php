<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Models\CRM\Institution;
use Illuminate\Support\Carbon;

// BRD: CRM-AR-021 — Aggregate analytics data layer for external BI API consumers (Power BI, Tableau)
final class AnalyticsApiService
{
    private const PII_KEYS = ['name', 'email', 'phone', 'mobile', 'first_name', 'last_name', 'full_name'];

    public function __construct(
        private readonly FunnelAnalyticsService      $funnelService,
        private readonly InstitutionDashboardService $institutionService,
        private readonly CounsellorDashboardService  $counsellorService,
        private readonly ReportService               $reportService,
    ) {}

    /**
     * Lead funnel metrics: stage counts, conversion rates, and per-source breakdown.
     *
     * @return array{stages: list<array<string, mixed>>, by_source: list<array<string, mixed>>}
     */
    public function getLeadFunnelMetrics(Institution $institution, Carbon $from, Carbon $to): array
    {
        $scope   = $this->buildScope($institution);
        $filters = $this->buildFilters($from, $to);

        $stages   = $this->funnelService->getFunnelStages($scope, $filters);
        $bySource = $this->funnelService->getFunnelBySource($scope, $filters)->toArray();

        return [
            'stages'    => $this->stripPii($stages),
            'by_source' => $this->stripPii($bySource),
        ];
    }

    /**
     * Application pipeline: counts by programme and status bucket.
     *
     * @return list<array<string, mixed>>
     */
    public function getPipelineMetrics(Institution $institution, Carbon $from, Carbon $to): array
    {
        $scope   = $this->buildScope($institution);
        $filters = $this->buildFilters($from, $to);

        return $this->stripPii(
            $this->institutionService->getByProgramme($scope, $filters)->toArray()
        );
    }

    /**
     * Fee collection summary: aggregate collected, pending, refunded amounts.
     *
     * @return array{summary: array<string, mixed>}
     */
    public function getFeeCollectionMetrics(Institution $institution, Carbon $from, Carbon $to): array
    {
        $scope   = $this->buildScope($institution);
        $filters = $this->buildFilters($from, $to);

        $summary = $this->reportService->feeCollectionSummary($scope, $filters);

        return [
            'collected'            => (float) $summary->collected,
            'pending_amount'       => (float) $summary->pending_amount,
            'refunded'             => (float) $summary->refunded,
            'total_transactions'   => (int)   $summary->total_transactions,
            'successful_count'     => (int)   $summary->successful_count,
        ];
    }

    /**
     * Counsellor performance grid: per-counsellor leads, conversions, tasks, response time.
     * Counsellor names are stripped to enforce no-PII contract (only counsellor_id returned).
     *
     * @return list<array<string, mixed>>
     */
    public function getCounsellorPerformanceMetrics(Institution $institution, Carbon $from, Carbon $to): array
    {
        $scope   = $this->buildScope($institution);
        $filters = $this->buildFilters($from, $to);

        return $this->stripPii(
            $this->counsellorService->getPerformanceGrid($scope, $filters)->toArray()
        );
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /** @return array{institution_id: int, campus_id: null, counsellor_ids: null, role: string} */
    private function buildScope(Institution $institution): array
    {
        return [
            'institution_id' => $institution->id,
            'campus_id'      => null,
            'counsellor_ids' => null,
            'role'           => 'director',
        ];
    }

    /** @return array{from: string, to: string} */
    private function buildFilters(Carbon $from, Carbon $to): array
    {
        return [
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
        ];
    }

    /**
     * Recursively remove PII keys from any array or collection of arrays/objects.
     *
     * @param array<int|string, mixed>|list<mixed> $items
     * @return list<array<string, mixed>>
     */
    private function stripPii(array $items): array
    {
        return array_values(array_map(function (mixed $item): array {
            $row = is_object($item) ? (array) $item : (array) $item;
            foreach (self::PII_KEYS as $key) {
                unset($row[$key]);
            }
            return $row;
        }, $items));
    }
}
