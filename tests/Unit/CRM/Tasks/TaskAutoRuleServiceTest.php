<?php

declare(strict_types=1);

// BRD: CRM-TF-002 — TaskAutoRuleService unit tests

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskType;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\CRM\Tasks\TaskAutoRule;
use App\Models\User;
use App\Services\CRM\Tasks\TaskAutoRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TaskAutoRuleService (CRM-TF-002)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->owner       = User::factory()->for($this->institution)->create();
        $this->lead        = Lead::factory()->for($this->institution)->create([
            'assigned_counsellor_id' => $this->owner->id,
            'last_contact_at'        => now()->subHours(49),
        ]);
        $this->rule = TaskAutoRule::factory()->for($this->institution)->create([
            'trigger_type'               => 'inactivity',
            'inactivity_threshold_hours' => 48,
            'task_type'                  => TaskType::Call->value,
            'priority'                   => TaskPriority::Normal->value,
            'assignee_strategy'          => 'lead_owner',
            'is_active'                  => true,
        ]);
        $this->service = app(TaskAutoRuleService::class);
    });

    it('creates an auto-task when lead has been inactive past threshold', function () {
        $task = $this->service->evaluateRuleForLead($this->rule, $this->lead);

        expect($task)->toBeInstanceOf(Task::class)
            ->and($task->source)->toBe(TaskSource::Auto)
            ->and($task->auto_rule_id)->toBe($this->rule->id);
    });

    it('skips auto-task creation when one already exists within 24h (dedup)', function () {
        Task::factory()->for($this->institution)->create([
            'lead_id'      => $this->lead->id,
            'auto_rule_id' => $this->rule->id,
            'source'       => TaskSource::Auto,
            'created_at'   => now()->subHours(2),
        ]);

        $task = $this->service->evaluateRuleForLead($this->rule, $this->lead);

        expect($task)->toBeNull();
    });

    it('assigns task to lead owner when assignee_strategy is lead_owner', function () {
        $task = $this->service->evaluateRuleForLead($this->rule, $this->lead);

        expect($task->assigned_to)->toBe($this->owner->id);
    });

});
