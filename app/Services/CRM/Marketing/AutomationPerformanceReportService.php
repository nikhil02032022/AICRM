<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\WorkflowActionExecution;
use App\Models\CRM\WorkflowInstance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

// BRD: CRM-MA-010 — Build automation workflow performance report aggregates
final class AutomationPerformanceReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReport(int $institutionId, int $days = 30, ?string $workflowUuid = null): array
    {
        $since = CarbonImmutable::now()->subDays(max(1, $days));

        $workflows = AutomationWorkflow::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereNull('deleted_at')
            ->when(
                $workflowUuid !== null,
                fn ($query) => $query->where('uuid', $workflowUuid),
            )
            ->get(['id', 'uuid', 'name', 'status', 'trigger_type']);

        if ($workflows->isEmpty()) {
            return [
                'filters' => [
                    'days' => max(1, $days),
                    'workflow_uuid' => $workflowUuid,
                    'since' => $since->toIso8601String(),
                ],
                'summary' => [
                    'workflows_count' => 0,
                    'instances_total' => 0,
                    'instances_completed' => 0,
                    'instances_failed' => 0,
                    'actions_total' => 0,
                    'actions_success' => 0,
                    'actions_failed' => 0,
                    'completion_rate' => 0.0,
                    'action_success_rate' => 0.0,
                ],
                'workflows' => [],
            ];
        }

        $workflowIds = $workflows->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

        $instanceStats = WorkflowInstance::withoutGlobalScopes()
            ->selectRaw('automation_workflow_id')
            ->selectRaw('COUNT(*) as instances_total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as instances_completed")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as instances_failed")
            ->selectRaw("SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as instances_running")
            ->where('institution_id', $institutionId)
            ->whereIn('automation_workflow_id', $workflowIds)
            ->where('created_at', '>=', $since)
            ->groupBy('automation_workflow_id')
            ->get()
            ->keyBy('automation_workflow_id');

        $actionStats = WorkflowActionExecution::withoutGlobalScopes()
            ->join('workflow_instances', 'workflow_instances.id', '=', 'workflow_action_executions.workflow_instance_id')
            ->where('workflow_action_executions.institution_id', $institutionId)
            ->whereIn('workflow_instances.automation_workflow_id', $workflowIds)
            ->where('workflow_action_executions.created_at', '>=', $since)
            ->groupBy('workflow_instances.automation_workflow_id')
            ->selectRaw('workflow_instances.automation_workflow_id as workflow_id')
            ->selectRaw('COUNT(*) as actions_total')
            ->selectRaw("SUM(CASE WHEN workflow_action_executions.status = 'success' THEN 1 ELSE 0 END) as actions_success")
            ->selectRaw("SUM(CASE WHEN workflow_action_executions.status = 'failed' THEN 1 ELSE 0 END) as actions_failed")
            ->get()
            ->keyBy('workflow_id');

        $rows = $workflows->map(function (AutomationWorkflow $workflow) use ($instanceStats, $actionStats): array {
            $instance = $instanceStats->get($workflow->id);
            $action = $actionStats->get($workflow->id);

            $instancesTotal = (int) ($instance->instances_total ?? 0);
            $instancesCompleted = (int) ($instance->instances_completed ?? 0);
            $instancesFailed = (int) ($instance->instances_failed ?? 0);
            $instancesRunning = (int) ($instance->instances_running ?? 0);

            $actionsTotal = (int) ($action->actions_total ?? 0);
            $actionsSuccess = (int) ($action->actions_success ?? 0);
            $actionsFailed = (int) ($action->actions_failed ?? 0);

            return [
                'workflow_uuid' => $workflow->uuid,
                'workflow_name' => $workflow->name,
                'workflow_status' => (string) $workflow->status?->value,
                'trigger_type' => $workflow->trigger_type,
                'instances_total' => $instancesTotal,
                'instances_completed' => $instancesCompleted,
                'instances_failed' => $instancesFailed,
                'instances_running' => $instancesRunning,
                'completion_rate' => $this->percent($instancesCompleted, $instancesTotal),
                'actions_total' => $actionsTotal,
                'actions_success' => $actionsSuccess,
                'actions_failed' => $actionsFailed,
                'action_success_rate' => $this->percent($actionsSuccess, $actionsTotal),
            ];
        })->values();

        return [
            'filters' => [
                'days' => max(1, $days),
                'workflow_uuid' => $workflowUuid,
                'since' => $since->toIso8601String(),
            ],
            'summary' => $this->summarize($rows),
            'workflows' => $rows,
            'generated_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int|float>
     */
    private function summarize(Collection $rows): array
    {
        $instancesTotal = (int) $rows->sum('instances_total');
        $instancesCompleted = (int) $rows->sum('instances_completed');
        $instancesFailed = (int) $rows->sum('instances_failed');
        $actionsTotal = (int) $rows->sum('actions_total');
        $actionsSuccess = (int) $rows->sum('actions_success');
        $actionsFailed = (int) $rows->sum('actions_failed');

        return [
            'workflows_count' => $rows->count(),
            'instances_total' => $instancesTotal,
            'instances_completed' => $instancesCompleted,
            'instances_failed' => $instancesFailed,
            'actions_total' => $actionsTotal,
            'actions_success' => $actionsSuccess,
            'actions_failed' => $actionsFailed,
            'completion_rate' => $this->percent($instancesCompleted, $instancesTotal),
            'action_success_rate' => $this->percent($actionsSuccess, $actionsTotal),
        ];
    }

    private function percent(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 2);
    }
}
