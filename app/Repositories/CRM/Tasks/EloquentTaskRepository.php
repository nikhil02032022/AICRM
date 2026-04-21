<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh();
    }

    /** @param array<string, mixed> $filters */
    public function paginateForCounsellor(User $user, array $filters = []): LengthAwarePaginator
    {
        return Task::select([
            'id', 'uuid', 'lead_id', 'assigned_to', 'title', 'type',
            'priority', 'status', 'disposition', 'source', 'due_at',
            'completed_at', 'overdue_flagged_at', 'created_at',
        ])
            ->where('assigned_to', $user->id)
            ->when(filled($filters['status'] ?? ''), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['type'] ?? ''), fn ($q) => $q->where('type', $filters['type']))
            ->when(filled($filters['priority'] ?? ''), fn ($q) => $q->where('priority', $filters['priority']))
            ->when(filled($filters['search'] ?? ''), fn ($q) => $q->where('title', 'like', "%{$filters['search']}%"))
            ->when(filled($filters['due_from'] ?? ''), fn ($q) => $q->where('due_at', '>=', $filters['due_from']))
            ->when(filled($filters['due_to'] ?? ''), fn ($q) => $q->where('due_at', '<=', $filters['due_to']))
            ->with(['lead:id,uuid,first_name,last_name'])
            ->orderByRaw('FIELD(priority, \'urgent\', \'high\', \'normal\', \'low\')')
            ->orderBy('due_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    /** @param array<string, mixed> $filters */
    public function paginateForManager(int $institutionId, array $filters = []): LengthAwarePaginator
    {
        return Task::select([
            'id', 'uuid', 'lead_id', 'assigned_to', 'title', 'type',
            'priority', 'status', 'disposition', 'source', 'due_at',
            'completed_at', 'overdue_flagged_at', 'created_at',
        ])
            ->where('institution_id', $institutionId)
            ->when(isset($filters['assigned_to']), fn ($q) => $q->where('assigned_to', $filters['assigned_to']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['due_from']), fn ($q) => $q->where('due_at', '>=', $filters['due_from']))
            ->when(isset($filters['due_to']), fn ($q) => $q->where('due_at', '<=', $filters['due_to']))
            ->with(['lead:id,uuid,first_name,last_name', 'assignee:id,name'])
            ->orderByRaw('FIELD(priority, \'urgent\', \'high\', \'normal\', \'low\')')
            ->orderBy('due_at')
            ->paginate($filters['per_page'] ?? 25);
    }

    public function findOverdue(int $institutionId): Collection
    {
        return Task::where('institution_id', $institutionId)
            ->where('due_at', '<', now())
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
            ->whereNull('overdue_flagged_at')
            ->with(['assignee:id,name,email'])
            ->get();
    }

    public function existsAutoTaskForLeadAndRule(int $leadId, int $autoRuleId, Carbon $since): bool
    {
        return Task::where('lead_id', $leadId)
            ->where('auto_rule_id', $autoRuleId)
            ->where('created_at', '>=', $since)
            ->whereNotIn('status', [TaskStatus::Cancelled->value])
            ->exists();
    }

    /** @param list<int> $taskIds */
    public function bulkUpdateAssignee(array $taskIds, int $assigneeId, int $institutionId): int
    {
        return Task::whereIn('id', $taskIds)
            ->where('institution_id', $institutionId)
            ->update(['assigned_to' => $assigneeId]);
    }

    public function calendarEventsFor(User $user, Carbon $start, Carbon $end): Collection
    {
        return Task::select([
            'id', 'uuid', 'lead_id', 'assigned_to', 'title',
            'type', 'priority', 'status', 'due_at',
        ])
            ->where('assigned_to', $user->id)
            ->whereBetween('due_at', [$start, $end])
            ->whereNotIn('status', [TaskStatus::Cancelled->value])
            ->with(['lead:id,uuid,first_name,last_name'])
            ->get();
    }
}
