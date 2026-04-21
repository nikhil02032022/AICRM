<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CRM\Tasks;

use App\DTOs\CRM\Tasks\CompleteTaskDTO;
use App\DTOs\CRM\Tasks\CreateTaskDTO;
use App\Http\Requests\CRM\Tasks\CompleteTaskRequest;
use App\Http\Requests\CRM\Tasks\StoreTaskRequest;
use App\Http\Requests\CRM\Tasks\UpdateTaskRequest;
use App\Http\Resources\CRM\Tasks\TaskResource;
use App\Models\CRM\Task;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;
use App\Services\CRM\Tasks\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-TF-001 to TF-005 — RESTful API for task management
final class TaskApiController
{
    use ApiResponse;

    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Task::class);

        $paginator = $this->tasks->paginateForCounsellor(
            $request->user(),
            $request->only(['status', 'type', 'date_from', 'date_to', 'per_page']),
        );

        return $this->successPaginated($paginator);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        Gate::authorize('create', Task::class);

        $task = $this->taskService->create(
            CreateTaskDTO::fromRequest($request->validated()),
            $request->user(),
        );

        return $this->success(new TaskResource($task->load(['lead', 'assignee'])), 'Task created.', 201);
    }

    public function show(Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        return $this->success(new TaskResource($task->load(['lead', 'assignee'])));
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $updated = $this->taskService->update($task, $request->validated());

        return $this->success(new TaskResource($updated->load(['lead', 'assignee'])), 'Task updated.');
    }

    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $this->taskService->cancel($task, request()->user());

        return $this->success(null, 'Task cancelled.');
    }

    // BRD: CRM-TF-005 — Complete task with mandatory disposition
    public function complete(Task $task, CompleteTaskRequest $request): JsonResponse
    {
        Gate::authorize('complete', $task);

        $dto     = CompleteTaskDTO::fromRequest($task->id, $request->validated());
        $updated = $this->taskService->complete($task, $dto);

        return $this->success(new TaskResource($updated->load(['lead', 'assignee'])), 'Task completed.');
    }
}
