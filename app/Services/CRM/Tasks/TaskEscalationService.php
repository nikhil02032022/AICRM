<?php

declare(strict_types=1);

namespace App\Services\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Task;
use App\Models\CRM\Tasks\TaskEscalationRule;
use App\Models\User;
use App\Notifications\CRM\Tasks\OverdueTaskAlert;
use App\Notifications\CRM\Tasks\TaskEscalationNotification;
use App\Repositories\CRM\Tasks\TaskEscalationRuleRepositoryInterface;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;

// BRD: CRM-TF-004 — Detect overdue tasks, flag and escalate per institution rules
final class TaskEscalationService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskEscalationRuleRepositoryInterface $escalationRules,
    ) {}

    public function detectAndFlagOverdue(Institution $institution): int
    {
        $rules = $this->escalationRules->activeRulesFor($institution->id);
        $overdueTasks = $this->tasks->findOverdue($institution->id);
        $flagged = 0;

        foreach ($overdueTasks as $task) {
            $this->escalateTask($task, $rules);
            $flagged++;
        }

        return $flagged;
    }

    /** @param \Illuminate\Database\Eloquent\Collection<int, TaskEscalationRule> $rules */
    public function escalateTask(Task $task, iterable $rules): void
    {
        // Flag the task as overdue
        $task->update([
            'status'             => TaskStatus::Overdue->value,
            'overdue_flagged_at' => now(),
        ]);

        // Notify the task assignee
        if ($task->assignee) {
            $task->assignee->notify(new OverdueTaskAlert($task));
        }

        // Apply escalation rules for this institution
        foreach ($rules as $rule) {
            $overdueHours = $task->due_at->diffInHours(now());

            if ($overdueHours >= $rule->overdue_threshold_hours) {
                $this->notifyEscalationRole($task, $rule);
            }
        }
    }

    private function notifyEscalationRole(Task $task, TaskEscalationRule $rule): void
    {
        $managers = User::where('institution_id', $task->institution_id)
            ->whereHas('roles', fn ($q) => $q->where('id', $rule->escalate_to_role_id))
            ->get();

        foreach ($managers as $manager) {
            $manager->notify(new TaskEscalationNotification($task, $manager));
        }
    }
}
