<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Tasks;

use App\DTOs\CRM\Tasks\CompleteTaskDTO;
use App\Enums\CRM\Tasks\TaskDisposition;
use App\Http\Requests\CRM\Tasks\CompleteTaskRequest;
use App\Models\CRM\Task;
use App\Services\CRM\Tasks\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-TF-005 — Task completion with mandatory disposition
final class TaskCompleteController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function create(Task $task): View
    {
        Gate::authorize('complete', $task);

        return view('crm.tasks.complete', [
            'task'         => $task->load(['lead', 'assignee']),
            'dispositions' => TaskDisposition::cases(),
        ]);
    }

    public function store(Task $task, CompleteTaskRequest $request): RedirectResponse
    {
        Gate::authorize('complete', $task);

        $dto = CompleteTaskDTO::fromRequest($task->id, $request->validated());
        $this->taskService->complete($task, $dto);

        return redirect()
            ->route('crm.leads.show', $task->lead->uuid)
            ->with('success', 'Task marked as complete.');
    }
}
