<?php

declare(strict_types=1);

namespace App\Observers\CRM\Tasks;

use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Task;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-TF-001, CRM-TF-005 — Records task lifecycle events on the lead's activity timeline
final class TaskObserver
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function created(Task $task): void
    {
        $this->activityRepository->createForSubject(new CreateActivityDTO(
            type:          ActivityType::TASK_CREATED,
            subjectType:   'App\Models\CRM\Lead',
            subjectId:     $task->lead_id,
            institutionId: $task->institution_id,
            body:          "Task created: {$task->title}",
            channel:       null,
            direction:     'internal',
            metadata:      [
                'task_uuid' => $task->uuid,
                'task_type' => $task->type?->value,
                'priority'  => $task->priority?->value,
                'due_at'    => $task->due_at?->toDateTimeString(),
                'source'    => $task->source?->value,
            ],
            performedById: $task->created_by,
        ));
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('status') && $task->status === TaskStatus::Completed) {
            $this->activityRepository->createForSubject(new CreateActivityDTO(
                type:          ActivityType::TASK_COMPLETED,
                subjectType:   'App\Models\CRM\Lead',
                subjectId:     $task->lead_id,
                institutionId: $task->institution_id,
                body:          "Task completed: {$task->title}",
                channel:       null,
                direction:     'internal',
                metadata:      [
                    'task_uuid'   => $task->uuid,
                    'disposition' => $task->disposition?->value,
                    'completed_at' => $task->completed_at?->toDateTimeString(),
                ],
                performedById: $task->assigned_to,
            ));
        } elseif ($task->wasChanged(['type', 'priority', 'due_at', 'assigned_to'])) {
            $this->activityRepository->createForSubject(new CreateActivityDTO(
                type:          ActivityType::TASK_UPDATED,
                subjectType:   'App\Models\CRM\Lead',
                subjectId:     $task->lead_id,
                institutionId: $task->institution_id,
                body:          "Task updated: {$task->title}",
                channel:       null,
                direction:     'internal',
                metadata:      ['task_uuid' => $task->uuid],
                performedById: $task->created_by,
            ));
        }
    }
}
