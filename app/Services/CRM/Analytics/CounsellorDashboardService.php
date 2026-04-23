<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-AR-002 — Counsellor performance dashboard: leads, tasks, conversion rate, response time per counsellor
final class CounsellorDashboardService
{
    public function getPerformanceGrid(array $scope, array $filters): Collection
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        // Base counsellor IDs to include
        $counsellorIds = $scope['counsellor_ids']; // null = all in institution
        $institutionId = $scope['institution_id'];

        $leadsQuery = Lead::withoutGlobalScopes()
            ->select(
                'assigned_counsellor_id as counsellor_id',
                DB::raw('COUNT(*) as total_leads'),
                DB::raw('SUM(CASE WHEN status IN ("enrolled","converted") THEN 1 ELSE 0 END) as total_converted'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, first_contacted_at)) as avg_response_hours'),
            )
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNotNull('assigned_counsellor_id')
            ->when($counsellorIds, fn ($q) => $q->whereIn('assigned_counsellor_id', $counsellorIds))
            ->groupBy('assigned_counsellor_id');

        $tasksQuery = Task::withoutGlobalScopes()
            ->select(
                'assigned_to as counsellor_id',
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as tasks_completed'),
            )
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($counsellorIds, fn ($q) => $q->whereIn('assigned_to', $counsellorIds))
            ->groupBy('assigned_to');

        $leads = $leadsQuery->get()->keyBy('counsellor_id');
        $tasks = $tasksQuery->get()->keyBy('counsellor_id');

        // Merge into unified grid keyed by counsellor_id
        $allIds = $leads->keys()->merge($tasks->keys())->unique();

        return $allIds->map(function ($counsellorId) use ($leads, $tasks) {
            $l = $leads->get($counsellorId);
            $t = $tasks->get($counsellorId);

            $totalLeads     = (int) ($l->total_leads ?? 0);
            $totalConverted = (int) ($l->total_converted ?? 0);
            $totalTasks     = (int) ($t->total_tasks ?? 0);
            $tasksCompleted = (int) ($t->tasks_completed ?? 0);

            return (object) [
                'counsellor_id'       => $counsellorId,
                'total_leads'         => $totalLeads,
                'total_converted'     => $totalConverted,
                'conversion_rate'     => $totalLeads > 0 ? round(($totalConverted / $totalLeads) * 100, 1) : 0,
                'total_tasks'         => $totalTasks,
                'tasks_completed'     => $tasksCompleted,
                'avg_response_hours'  => round((float) ($l->avg_response_hours ?? 0), 1),
            ];
        })->sortByDesc('total_leads')->values();
    }

    public function getOwnKpis(int $counsellorId, int $institutionId, array $filters): object
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['to']   ?? now()->toDateString();

        $scope = ['institution_id' => $institutionId, 'counsellor_ids' => [$counsellorId]];

        return $this->getPerformanceGrid($scope, ['from' => $from, 'to' => $to])->first()
            ?? (object) [
                'counsellor_id'      => $counsellorId,
                'total_leads'        => 0,
                'total_converted'    => 0,
                'conversion_rate'    => 0,
                'total_tasks'        => 0,
                'tasks_completed'    => 0,
                'avg_response_hours' => 0,
            ];
    }
}
