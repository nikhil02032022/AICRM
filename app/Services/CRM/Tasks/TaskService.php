<?php

declare(strict_types=1);

namespace App\Services\CRM\Tasks;

use App\DTOs\CRM\Tasks\BulkAssignTaskDTO;
use App\DTOs\CRM\Tasks\CompleteTaskDTO;
use App\DTOs\CRM\Tasks\CreateTaskDTO;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Events\CRM\Tasks\TaskAssignedEvent;
use App\Events\CRM\Tasks\TaskBulkAssignedEvent;
use App\Events\CRM\Tasks\TaskCompletedEvent;
use App\Events\CRM\Tasks\TaskCreatedEvent;
use App\Models\CRM\Task;
use App\Models\User;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;
use Illuminate\Validation\ValidationException;

// BRD: CRM-TF-001, CRM-TF-005, CRM-TF-008
final class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    // BRD: CRM-TF-001 — Counsellors create tasks of any type linked to a lead
    public function create(CreateTaskDTO $dto, User $creator): Task
    {
        $task = $this->tasks->create([
            'institution_id' => $creator->institution_id,
            'lead_id'        => $dto->leadId,
            'assigned_to'    => $dto->assignedTo ?? $creator->id,
            'created_by'     => $creator->id,
            'type'           => $dto->type->value,
            'priority'       => $dto->priority->value,
            'title'          => $dto->title,
            'description'    => $dto->description,
            'status'         => TaskStatus::Pending->value,
            'source'         => $dto->source->value,
            'auto_rule_id'   => $dto->autoRuleId,
            'due_at'         => $dto->dueAt,
        ]);

        TaskCreatedEvent::dispatch($task);

        if ($task->assigned_to !== $creator->id) {
            $assignee = User::find($task->assigned_to);
            if ($assignee) {
                TaskAssignedEvent::dispatch($task, $assignee);
            }
        }

        return $task;
    }

    // BRD: CRM-TF-005 — Task completion requires a disposition/outcome
    public function complete(Task $task, CompleteTaskDTO $dto): Task
    {
        if ($dto->disposition === null) {
            throw new \InvalidArgumentException('Disposition is required to complete a task.');
        }

        throw_if(
            $task->status->isTerminal(),
            ValidationException::withMessages(['status' => 'Task is already completed or cancelled.']),
        );

        $this->tasks->update($task, [
            'status'       => TaskStatus::Completed->value,
            'disposition'  => $dto->disposition->value,
            'completed_at' => $dto->completedAt,
            'description'  => $dto->notes ?? $task->description,
        ]);

        $task->refresh();
        TaskCompletedEvent::dispatch($task);

        return $task;
    }

    // BRD: CRM-TF-008 — Bulk task assignment validated atomically
    public function bulkAssign(BulkAssignTaskDTO $dto, User $actor): int
    {
        // Resolve UUIDs to internal IDs, validating institution ownership
        $taskIds = Task::whereIn('uuid', $dto->taskUuids)
            ->where('institution_id', $dto->institutionId)
            ->pluck('id')
            ->toArray();

        if (count($taskIds) !== count($dto->taskUuids)) {
            throw ValidationException::withMessages([
                'task_uuids' => 'One or more task UUIDs are invalid or belong to another institution.',
            ]);
        }

        $count = $this->tasks->bulkUpdateAssignee($taskIds, $dto->assigneeId, $dto->institutionId);

        $assignee = User::find($dto->assigneeId);
        if ($assignee) {
            TaskBulkAssignedEvent::dispatch($taskIds, $assignee);
        }

        return $count;
    }

    public function update(Task $task, array $data): Task
    {
        return $this->tasks->update($task, $data);
    }

    public function cancel(Task $task, User $actor): Task
    {
        return $this->tasks->update($task, ['status' => TaskStatus::Cancelled->value]);
    }
}
