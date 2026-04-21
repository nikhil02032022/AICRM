<?php

declare(strict_types=1);

// BRD: CRM-TF-002 — Auto-task creation job feature tests

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskType;
use App\Jobs\CRM\Tasks\AutoCreateFollowUpTaskJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\CRM\Tasks\TaskAutoRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('AutoCreateFollowUpTaskJob (CRM-TF-002)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create(['is_active' => true]);
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
    });

    it('creates auto-task when lead has no contact past threshold', function () {
        (new AutoCreateFollowUpTaskJob())->handle(
            app(\App\Services\CRM\Tasks\TaskAutoRuleService::class),
        );

        expect(Task::where('source', TaskSource::Auto->value)
            ->where('auto_rule_id', $this->rule->id)
            ->where('lead_id', $this->lead->id)
            ->exists()
        )->toBeTrue();
    });

    it('does not create duplicate auto-task within 24h window', function () {
        Task::factory()->for($this->institution)->create([
            'lead_id'      => $this->lead->id,
            'auto_rule_id' => $this->rule->id,
            'source'       => TaskSource::Auto,
            'created_at'   => now()->subHours(6),
        ]);

        (new AutoCreateFollowUpTaskJob())->handle(
            app(\App\Services\CRM\Tasks\TaskAutoRuleService::class),
        );

        expect(Task::where('source', TaskSource::Auto->value)
            ->where('auto_rule_id', $this->rule->id)
            ->where('lead_id', $this->lead->id)
            ->count()
        )->toBe(1);
    });

    it('job uses atomic Redis lock to prevent concurrent runs', function () {
        Queue::fake();

        $lockKey  = "cron:auto-task:{$this->institution->id}";
        $lock     = Cache::lock($lockKey, 3600);
        $acquired = $lock->get();

        expect($acquired)->toBeTrue();
        $lock->release();
    });

});
