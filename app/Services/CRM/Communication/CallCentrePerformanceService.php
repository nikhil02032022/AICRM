<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\LeadStatus;
use App\Models\CRM\CallLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// BRD: CRM-TC-007 — Call centre performance dashboard service
final class CallCentrePerformanceService
{
    /**
     * Generate comprehensive per-agent performance metrics.
     *
     * @param  int     $institutionId
     * @param  string  $fromDate YYYY-MM-DD
     * @param  string  $toDate   YYYY-MM-DD
     * @param  int|null $agentId  Filter by specific agent ID
     * @return array<string, mixed>
     */
    public function buildPerformanceReport(
        int $institutionId,
        string $fromDate,
        string $toDate,
        ?int $agentId = null,
    ): array {
        $query = CallLog::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereDate('called_at', '>=', $fromDate)
            ->whereDate('called_at', '<=', $toDate)
            ->where('direction', CallDirection::OUTBOUND);

        if ($agentId !== null) {
            $query->where('initiated_by', $agentId);
        }

        $agentPerformance = $query
            ->select(
                'initiated_by',
                DB::raw('COUNT(*) as calls_made'),
                DB::raw('SUM(CASE WHEN status = "COMPLETED" THEN 1 ELSE 0 END) as connects'),
                DB::raw('SUM(CASE WHEN status = "COMPLETED" THEN duration_seconds ELSE 0 END) as total_talk_time'),
                DB::raw('AVG(CASE WHEN status = "COMPLETED" THEN duration_seconds ELSE 0 END) as avg_talk_time'),
            )
            ->groupBy('initiated_by')
            ->get()
            ->keyBy('initiated_by');

        $conversionMap = $this->calculateConversionsPerAgent($institutionId, $fromDate, $toDate, $agentId);

        $agentIds = $agentPerformance->pluck('initiated_by')->merge(array_keys($conversionMap))->unique();

        $agents = User::withoutGlobalScopes()
            ->whereIn('id', $agentIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = [];

        foreach ($agentIds as $userId) {
            $perf = $agentPerformance->get($userId);
            $callsMade = (int) ($perf?->calls_made ?? 0);
            $connects = (int) ($perf?->connects ?? 0);
            $totalTalkTime = (int) ($perf?->total_talk_time ?? 0);
            $avgTalkTime = (int) ($perf?->avg_talk_time ?? 0);
            $conversions = (int) ($conversionMap[$userId] ?? 0);

            $connectRate = $callsMade > 0 ? round(($connects / $callsMade) * 100, 1) : 0.0;
            $conversionRate = $connects > 0 ? round(($conversions / $connects) * 100, 1) : 0.0;

            $rows[] = [
                'agent_id' => (int) $userId,
                'agent_name' => (string) ($agents[$userId]->name ?? 'Unknown'),
                'calls_made' => $callsMade,
                'connects' => $connects,
                'total_talk_time_seconds' => $totalTalkTime,
                'avg_talk_time_seconds' => $avgTalkTime,
                'conversions' => $conversions,
                'connect_rate_percent' => $connectRate,
                'conversion_rate_percent' => $conversionRate,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['calls_made'] <=> $a['calls_made']);

        $summary = [
            'total_calls_made' => (int) array_sum(array_column($rows, 'calls_made')),
            'total_connects' => (int) array_sum(array_column($rows, 'connects')),
            'total_talk_time_seconds' => (int) array_sum(array_column($rows, 'total_talk_time_seconds')),
            'total_conversions' => (int) array_sum(array_column($rows, 'conversions')),
            'agent_count' => count($rows),
        ];

        return [
            'summary' => $summary,
            'per_agent' => $rows,
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ];
    }

    /**
     * Calculate lead conversions attributed to each agent within period.
     * A conversion is when a lead assigned to an agent transitions to ENROLLED or FEE_PAID.
     *
     * @param  int     $institutionId
     * @param  string  $fromDate YYYY-MM-DD
     * @param  string  $toDate   YYYY-MM-DD
     * @param  int|null $agentId
     * @return array<int, int>  Map of agent_id => conversion_count
     */
    private function calculateConversionsPerAgent(
        int $institutionId,
        string $fromDate,
        string $toDate,
        ?int $agentId = null,
    ): array {
        $query = DB::table('leads')
            ->where('institution_id', $institutionId)
            ->whereIn('status', [LeadStatus::ENROLLED->value, LeadStatus::FEE_PAID->value])
            ->whereDate('status_changed_at', '>=', $fromDate)
            ->whereDate('status_changed_at', '<=', $toDate)
            ->whereNotNull('assigned_counsellor_id')
            ->whereNull('deleted_at');

        if ($agentId !== null) {
            $query->where('assigned_counsellor_id', $agentId);
        }

        $conversions = $query
            ->select('assigned_counsellor_id', DB::raw('COUNT(*) as count'))
            ->groupBy('assigned_counsellor_id')
            ->get();

        $map = [];
        foreach ($conversions as $row) {
            $map[(int) $row->assigned_counsellor_id] = (int) $row->count;
        }

        return $map;
    }

    /**
     * Get daily call volume trend for charting.
     *
     * @param  int     $institutionId
     * @param  string  $fromDate YYYY-MM-DD
     * @param  string  $toDate   YYYY-MM-DD
     * @return list<array{date: string, calls: int, connects: int}>
     */
    public function getDailyCallVolumeTrend(
        int $institutionId,
        string $fromDate,
        string $toDate,
    ): array {
        $rows = CallLog::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereDate('called_at', '>=', $fromDate)
            ->whereDate('called_at', '<=', $toDate)
            ->where('direction', CallDirection::OUTBOUND)
            ->select(
                DB::raw('DATE(called_at) as call_date'),
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(CASE WHEN status = "COMPLETED" THEN 1 ELSE 0 END) as connects'),
            )
            ->groupBy('call_date')
            ->orderBy('call_date')
            ->get();

        return $rows->map(static fn (object $row): array => [
            'date' => (string) $row->call_date,
            'calls' => (int) $row->calls,
            'connects' => (int) $row->connects,
        ])->toArray();
    }
}
