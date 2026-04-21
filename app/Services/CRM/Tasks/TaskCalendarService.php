<?php

declare(strict_types=1);

namespace App\Services\CRM\Tasks;

use App\DTOs\CRM\Tasks\TaskCalendarQueryDTO;
use App\Enums\CRM\Tasks\TaskPriority;
use App\Models\CRM\Task;
use App\Models\User;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;

// BRD: CRM-TF-009 — Calendar view of tasks (daily, weekly, monthly) for counsellors
final class TaskCalendarService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /** @return list<array<string, mixed>> */
    public function buildCalendarEvents(User $user, TaskCalendarQueryDTO $dto): array
    {
        $tasks = $this->tasks->calendarEventsFor($user, $dto->start, $dto->end);

        return $tasks->map(function (Task $task): array {
            return [
                'id'    => $task->uuid,
                'title' => $task->title,
                'start' => $task->due_at?->toIso8601String(),
                'end'   => $task->due_at?->addMinutes(30)->toIso8601String(),
                'color' => $this->priorityColour($task->priority),
                'extendedProps' => [
                    'type'     => $task->type?->value,
                    'priority' => $task->priority?->value,
                    'status'   => $task->status?->value,
                    'leadUuid' => $task->lead?->uuid,
                    'leadName' => $task->lead
                        ? "{$task->lead->first_name} {$task->lead->last_name}"
                        : null,
                ],
            ];
        })->values()->all();
    }

    private function priorityColour(?TaskPriority $priority): string
    {
        return match ($priority) {
            TaskPriority::Urgent => '#DC2626',
            TaskPriority::High   => '#EA580C',
            TaskPriority::Normal => '#2563EB',
            TaskPriority::Low    => '#6B7280',
            default              => '#2563EB',
        };
    }
}
