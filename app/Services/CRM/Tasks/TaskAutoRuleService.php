<?php

declare(strict_types=1);

namespace App\Services\CRM\Tasks;

use App\DTOs\CRM\Tasks\CreateTaskDTO;
use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskType;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\CRM\Tasks\TaskAutoRule;
use App\Models\User;
use App\Repositories\CRM\Tasks\TaskAutoRuleRepositoryInterface;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;

// BRD: CRM-TF-002 — Auto-create follow-up tasks from configurable inactivity rules
final class TaskAutoRuleService
{
    public function __construct(
        private readonly TaskAutoRuleRepositoryInterface $rules,
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskService $taskService,
    ) {}

    public function evaluateRulesForInstitution(Institution $institution): int
    {
        $activeRules = $this->rules->activeRulesFor($institution->id);
        $created = 0;

        foreach ($activeRules as $rule) {
            $threshold = now()->subHours($rule->inactivity_threshold_hours);

            // Find leads with no recent activity past the threshold
            $leads = Lead::where('institution_id', $institution->id)
                ->whereDoesntHave('activities', function ($q) use ($threshold): void {
                    $q->where('created_at', '>=', $threshold);
                })
                ->whereDoesntHave('tasks', function ($q): void {
                    $q->whereIn('status', ['pending', 'in_progress']);
                })
                ->get();

            foreach ($leads as $lead) {
                $task = $this->evaluateRuleForLead($rule, $lead);
                if ($task !== null) {
                    $created++;
                }
            }
        }

        return $created;
    }

    public function evaluateRuleForLead(TaskAutoRule $rule, Lead $lead): ?Task
    {
        // Dedup: skip if an auto-task for this rule+lead was created in the last 24 hours
        if ($this->tasks->existsAutoTaskForLeadAndRule($lead->id, $rule->id, now()->subHours(24))) {
            return null;
        }

        $assignee = $this->resolveAssignee($rule, $lead);

        $dto = new CreateTaskDTO(
            leadId:      $lead->id,
            type:        TaskType::from($rule->task_type),
            priority:    TaskPriority::from($rule->priority),
            title:       "Follow-up: {$lead->full_name}",
            description: "Auto-generated follow-up task based on inactivity rule.",
            dueAt:       now()->addHours(24),
            assignedTo:  $assignee?->id,
            source:      TaskSource::Auto,
            autoRuleId:  $rule->id,
        );

        // Use a synthetic creator user (lead owner or system)
        $creator = $assignee ?? User::find($lead->assigned_counsellor_id);
        if ($creator === null) {
            return null;
        }

        return $this->taskService->create($dto, $creator);
    }

    private function resolveAssignee(TaskAutoRule $rule, Lead $lead): ?User
    {
        return match ($rule->assignee_strategy) {
            'lead_owner'   => User::find($lead->assigned_counsellor_id),
            'round_robin'  => $this->resolveRoundRobin($lead->institution_id),
            'least_loaded' => $this->resolveLeastLoaded($lead->institution_id),
            default        => User::find($lead->assigned_counsellor_id),
        };
    }

    private function resolveRoundRobin(int $institutionId): ?User
    {
        return User::where('institution_id', $institutionId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'counsellor_senior')
                ->orWhere('name', 'counsellor_junior'))
            ->inRandomOrder()
            ->first();
    }

    private function resolveLeastLoaded(int $institutionId): ?User
    {
        return User::where('institution_id', $institutionId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['counsellor_senior', 'counsellor_junior']))
            ->withCount(['tasks' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress'])])
            ->orderBy('tasks_count')
            ->first();
    }
}
