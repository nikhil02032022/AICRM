<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Tasks;

use App\DTOs\CRM\Tasks\BulkAssignTaskDTO;
use App\Http\Requests\CRM\Tasks\BulkAssignTaskRequest;
use App\Services\CRM\Tasks\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-TF-008 — Bulk task assignment controller
final class TaskBulkController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function bulkAssign(BulkAssignTaskRequest $request): RedirectResponse
    {
        Gate::authorize('crm.tasks.bulk-assign');

        $dto   = BulkAssignTaskDTO::fromRequest($request->validated(), $request->user()->institution_id);
        $count = $this->taskService->bulkAssign($dto, $request->user());

        return redirect()
            ->back()
            ->with('success', "{$count} task(s) assigned successfully.");
    }

    public function bulkReassign(BulkAssignTaskRequest $request): RedirectResponse
    {
        Gate::authorize('crm.tasks.bulk-assign');

        $dto   = BulkAssignTaskDTO::fromRequest($request->validated(), $request->user()->institution_id);
        $count = $this->taskService->bulkAssign($dto, $request->user());

        return redirect()
            ->back()
            ->with('success', "{$count} task(s) reassigned successfully.");
    }
}
