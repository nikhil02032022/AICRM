<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CRM\Tasks;

use App\DTOs\CRM\Tasks\CreateTaskAutoRuleDTO;
use App\Http\Requests\CRM\Tasks\StoreTaskAutoRuleRequest;
use App\Http\Resources\CRM\Tasks\TaskAutoRuleResource;
use App\Models\CRM\Tasks\TaskAutoRule;
use App\Repositories\CRM\Tasks\TaskAutoRuleRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-TF-002 — RESTful API for task auto-rule management (institution admin only)
final class TaskAutoRuleApiController
{
    use ApiResponse;

    public function __construct(
        private readonly TaskAutoRuleRepositoryInterface $rules,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.task-auto-rules.manage');

        $rules = $this->rules->activeRulesFor($request->user()->institution_id);

        return $this->success(TaskAutoRuleResource::collection($rules));
    }

    public function store(StoreTaskAutoRuleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $rule = TaskAutoRule::create([
            'institution_id'             => $request->user()->institution_id,
            'campus_id'                  => $validated['campus_id'] ?? null,
            'trigger_type'               => $validated['trigger_type'],
            'inactivity_threshold_hours' => $validated['inactivity_threshold_hours'],
            'task_type'                  => $validated['task_type'],
            'priority'                   => $validated['priority'],
            'assignee_strategy'          => $validated['assignee_strategy'],
            'is_active'                  => true,
        ]);

        return $this->success(new TaskAutoRuleResource($rule), 'Auto-rule created.', 201);
    }

    public function show(TaskAutoRule $taskAutoRule): JsonResponse
    {
        Gate::authorize('crm.task-auto-rules.manage');

        return $this->success(new TaskAutoRuleResource($taskAutoRule));
    }

    public function update(StoreTaskAutoRuleRequest $request, TaskAutoRule $taskAutoRule): JsonResponse
    {
        $taskAutoRule->update($request->validated());

        return $this->success(new TaskAutoRuleResource($taskAutoRule->fresh()), 'Auto-rule updated.');
    }

    public function destroy(TaskAutoRule $taskAutoRule): JsonResponse
    {
        Gate::authorize('crm.task-auto-rules.manage');

        $taskAutoRule->delete();

        return $this->success(null, 'Auto-rule deleted.');
    }
}
