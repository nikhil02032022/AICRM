<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Automation;

use App\Models\CRM\WorkflowInstance;
use App\Services\CRM\Marketing\AutomationActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-MA-003 — Queue job executing workflow action steps for a workflow instance
final class ExecuteWorkflowActionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $institutionId,
        public readonly int $workflowInstanceId,
    ) {
        $this->onQueue('crm-automation');
    }

    public function handle(AutomationActionService $actionService): void
    {
        $instance = WorkflowInstance::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->whereKey($this->workflowInstanceId)
            ->first();

        if ($instance === null) {
            return;
        }

        if (! in_array((string) $instance->status, ['pending', 'running'], true)) {
            return;
        }

        $result = $actionService->executeDueActionForInstance($instance);

        if ($result['completed'] === true) {
            return;
        }

        $job = self::dispatch($this->institutionId, $this->workflowInstanceId);

        if ($result['next_run_at'] !== null) {
            $job->delay($result['next_run_at']);
        }
    }
}
