<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\DTOs\CRM\CreateAutomationWorkflowDTO;
use App\Enums\CRM\WorkflowNodeType;
use App\Enums\CRM\WorkflowStatus;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\WorkflowStep;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentAutomationWorkflowRepository implements AutomationWorkflowRepositoryInterface
{
    public function create(CreateAutomationWorkflowDTO $dto, int $institutionId, int $userId): AutomationWorkflow
    {
        return DB::transaction(function () use ($dto, $institutionId, $userId): AutomationWorkflow {
            $workflow = AutomationWorkflow::create([
                'institution_id' => $institutionId,
                'campus_id' => $dto->campusId,
                'created_by' => $userId,
                'name' => $dto->name,
                'description' => $dto->description,
                'status' => $dto->status->value,
                'trigger_type' => $dto->triggerType,
                'trigger_config' => $dto->triggerConfig,
                'version' => 1,
                'published_at' => $dto->status === WorkflowStatus::ACTIVE ? now() : null,
            ]);

            $this->syncSteps($workflow, $dto->steps);

            return $workflow->fresh(['steps', 'creator']);
        });
    }

    public function update(AutomationWorkflow $workflow, array $data): AutomationWorkflow
    {
        return DB::transaction(function () use ($workflow, $data): AutomationWorkflow {
            if (array_key_exists('status', $data)) {
                $status = $data['status'] instanceof WorkflowStatus
                    ? $data['status']
                    : WorkflowStatus::from((string) $data['status']);

                $data['status'] = $status->value;
                $data['published_at'] = $status === WorkflowStatus::ACTIVE
                    ? ($workflow->published_at ?? now())
                    : null;
            }

            $workflow->update($data);

            if (isset($data['steps']) && is_array($data['steps'])) {
                $this->syncSteps($workflow, $data['steps']);
            }

            return $workflow->fresh(['steps', 'creator']);
        });
    }

    public function softDelete(AutomationWorkflow $workflow): void
    {
        $workflow->delete();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = AutomationWorkflow::query()
            ->with(['creator'])
            ->withCount('steps');

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('trigger_type', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['trigger_type'])) {
            $query->where('trigger_type', (string) $filters['trigger_type']);
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    /** @param array<int, array<string, mixed>> $steps */
    private function syncSteps(AutomationWorkflow $workflow, array $steps): void
    {
        $workflow->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            $nodeType = $step['node_type'] ?? WorkflowNodeType::ACTION;

            WorkflowStep::create([
                'institution_id' => $workflow->institution_id,
                'campus_id' => $workflow->campus_id,
                'automation_workflow_id' => $workflow->id,
                'step_order' => isset($step['order']) ? (int) $step['order'] : $index,
                'node_type' => $nodeType instanceof WorkflowNodeType ? $nodeType->value : (string) $nodeType,
                'name' => (string) ($step['name'] ?? 'Step '.($index + 1)),
                'config' => isset($step['config']) && is_array($step['config']) ? $step['config'] : null,
                'delay_minutes' => isset($step['delay_minutes']) ? (int) $step['delay_minutes'] : null,
            ]);
        }
    }
}
