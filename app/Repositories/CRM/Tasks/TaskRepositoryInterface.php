<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Tasks;

use App\Models\CRM\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    /** @param array<string, mixed> $filters */
    public function paginateForCounsellor(User $user, array $filters = []): LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function paginateForManager(int $institutionId, array $filters = []): LengthAwarePaginator;

    public function findOverdue(int $institutionId): Collection;

    public function existsAutoTaskForLeadAndRule(int $leadId, int $autoRuleId, Carbon $since): bool;

    /** @param list<int> $taskIds */
    public function bulkUpdateAssignee(array $taskIds, int $assigneeId, int $institutionId): int;

    public function calendarEventsFor(User $user, Carbon $start, Carbon $end): Collection;
}
