<?php

declare(strict_types=1);

// BRD: CRM-TF-004 — TaskEscalationService unit tests

use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Task;
use App\Models\CRM\Tasks\TaskEscalationRule;
use App\Models\User;
use App\Notifications\CRM\Tasks\OverdueTaskAlert;
use App\Services\CRM\Tasks\TaskEscalationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('TaskEscalationService (CRM-TF-004)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->assignee    = User::factory()->for($this->institution)->create();
        $this->service     = app(TaskEscalationService::class);

        TaskEscalationRule::factory()->for($this->institution)->create([
            'overdue_threshold_hours' => 1,
            'notification_channel'    => 'in_app',
            'is_active'               => true,
        ]);
    });

    it('marks overdue task and sets overdue_flagged_at', function () {
        $task = Task::factory()->for($this->institution)->create([
            'status'             => TaskStatus::Pending,
            'assigned_to'        => $this->assignee->id,
            'due_at'             => now()->subHours(3),
            'overdue_flagged_at' => null,
        ]);

        $count = $this->service->detectAndFlagOverdue($this->institution);

        expect($count)->toBeGreaterThanOrEqual(1);
        expect($task->fresh()->status)->toBe(TaskStatus::Overdue)
            ->and($task->fresh()->overdue_flagged_at)->not->toBeNull();
    });

    it('fires OverdueTaskAlert notification to task assignee', function () {
        Notification::fake();

        $task = Task::factory()->for($this->institution)->create([
            'status'             => TaskStatus::Pending,
            'assigned_to'        => $this->assignee->id,
            'due_at'             => now()->subHours(3),
            'overdue_flagged_at' => null,
        ]);

        $this->service->detectAndFlagOverdue($this->institution);

        Notification::assertSentTo($this->assignee, OverdueTaskAlert::class);
    });

    it('does not re-escalate tasks that already have overdue_flagged_at set', function () {
        Notification::fake();

        $task = Task::factory()->for($this->institution)->create([
            'status'             => TaskStatus::Overdue,
            'assigned_to'        => $this->assignee->id,
            'due_at'             => now()->subHours(5),
            'overdue_flagged_at' => now()->subHours(2),
        ]);

        $count = $this->service->detectAndFlagOverdue($this->institution);

        expect($count)->toBe(0);
        Notification::assertNothingSent();
    });

});
