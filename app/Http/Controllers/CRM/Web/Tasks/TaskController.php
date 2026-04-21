<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Tasks;

use App\DTOs\CRM\Tasks\CreateTaskDTO;
use App\Enums\CRM\Tasks\TaskDisposition;
use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Enums\CRM\Tasks\TaskType;
use App\Http\Requests\CRM\Tasks\StoreTaskRequest;
use App\Http\Requests\CRM\Tasks\UpdateTaskRequest;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\User;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;
use App\Services\CRM\Tasks\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-TF-001 — Task CRUD web controller
final class TaskController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Task::class);

        return view('crm.tasks.index');
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Task::class);

        return view('crm.tasks.create', [
            'types'      => TaskType::cases(),
            'priorities' => TaskPriority::cases(),
            'leads'      => Lead::query()->select('id', 'first_name', 'last_name')->orderBy('first_name')->get(),
            'users'      => User::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Gate::authorize('create', Task::class);

        $dto  = CreateTaskDTO::fromRequest($request->validated());
        $task = $this->taskService->create($dto, $request->user());

        return redirect()
            ->route('crm.tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        Gate::authorize('update', $task);

        return view('crm.tasks.edit', [
            'task'       => $task->load(['lead', 'assignee']),
            'types'      => TaskType::cases(),
            'priorities' => TaskPriority::cases(),
            'statuses'   => TaskStatus::cases(),
            'leads'      => Lead::query()->select('id', 'first_name', 'last_name')->orderBy('first_name')->get(),
            'users'      => User::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $this->taskService->update($task, $request->validated());

        return redirect()
            ->route('crm.tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $this->taskService->cancel($task, request()->user());

        return redirect()
            ->route('crm.tasks.index')
            ->with('success', 'Task cancelled.');
    }
}
