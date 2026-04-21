<?php

declare(strict_types=1);

// BRD: CRM-TF-001, TF-005, TF-008 — TaskService unit tests

use App\DTOs\CRM\Tasks\BulkAssignTaskDTO;
use App\DTOs\CRM\Tasks\CompleteTaskDTO;
use App\DTOs\CRM\Tasks\CreateTaskDTO;
use App\Enums\CRM\Tasks\TaskDisposition;
use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Enums\CRM\Tasks\TaskType;
use App\Events\CRM\Tasks\TaskAssignedEvent;
use App\Events\CRM\Tasks\TaskBulkAssignedEvent;
use App\Events\CRM\Tasks\TaskCompletedEvent;
use App\Events\CRM\Tasks\TaskCreatedEvent;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\User;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;
use App\Services\CRM\Tasks\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('TaskService (CRM-TF-001)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->creator     = User::factory()->for($this->institution)->create();
        $this->lead        = Lead::factory()->for($this->institution)->create();
        $this->service     = app(TaskService::class);
    });

    it('creates a task and fires TaskCreatedEvent', function () {
        Event::fake([TaskCreatedEvent::class, TaskAssignedEvent::class]);

        $dto = new CreateTaskDTO(
            leadId: $this->lead->id,
            type: TaskType::Call,
            priority: TaskPriority::Normal,
            title: 'Follow-up call',
            description: null,
            dueAt: now()->addDay(),
            assignedTo: $this->creator->id,
            source: TaskSource::Manual,
            autoRuleId: null,
        );

        $task = $this->service->create($dto, $this->creator);

        expect($task)->toBeInstanceOf(Task::class)
            ->and($task->title)->toBe('Follow-up call')
            ->and($task->status)->toBe(TaskStatus::Pending);

        Event::assertDispatched(TaskCreatedEvent::class);
    });

    it('fires TaskAssignedEvent when assignee differs from creator', function () {
        Event::fake([TaskCreatedEvent::class, TaskAssignedEvent::class]);

        $assignee = User::factory()->for($this->institution)->create();

        $dto = new CreateTaskDTO(
            leadId: $this->lead->id,
            type: TaskType::Email,
            priority: TaskPriority::High,
            title: 'Send documents',
            description: null,
            dueAt: now()->addDay(),
            assignedTo: $assignee->id,
            source: TaskSource::Manual,
            autoRuleId: null,
        );

        $this->service->create($dto, $this->creator);

        Event::assertDispatched(TaskAssignedEvent::class);
    });

    it('does not fire TaskAssignedEvent when assignee is creator', function () {
        Event::fake([TaskCreatedEvent::class, TaskAssignedEvent::class]);

        $dto = new CreateTaskDTO(
            leadId: $this->lead->id,
            type: TaskType::Call,
            priority: TaskPriority::Normal,
            title: 'Self-assigned task',
            description: null,
            dueAt: now()->addDay(),
            assignedTo: $this->creator->id,
            source: TaskSource::Manual,
            autoRuleId: null,
        );

        $this->service->create($dto, $this->creator);

        Event::assertNotDispatched(TaskAssignedEvent::class);
    });

    it('throws when completing a task without disposition', function () {
        $task = Task::factory()->for($this->institution)->create([
            'status' => TaskStatus::Pending,
        ]);

        $dto = new CompleteTaskDTO(
            taskId: $task->id,
            disposition: null,
            notes: null,
            completedAt: now(),
        );

        expect(fn () => $this->service->complete($task, $dto))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('completes a task and sets completed_at to now', function () {
        Event::fake([TaskCompletedEvent::class]);

        $task = Task::factory()->for($this->institution)->create([
            'status' => TaskStatus::Pending,
        ]);

        $dto = CompleteTaskDTO::fromRequest($task->id, [
            'disposition' => TaskDisposition::ReachedInterested->value,
            'notes'       => 'Student confirmed interest.',
        ]);

        $completed = $this->service->complete($task, $dto);

        expect($completed->status)->toBe(TaskStatus::Completed)
            ->and($completed->completed_at)->not->toBeNull()
            ->and($completed->disposition)->toBe(TaskDisposition::ReachedInterested);

        Event::assertDispatched(TaskCompletedEvent::class);
    });

    it('bulk-assigns tasks and fires TaskBulkAssignedEvent', function () {
        Event::fake([TaskBulkAssignedEvent::class]);

        $assignee = User::factory()->for($this->institution)->create();
        $tasks    = Task::factory()->for($this->institution)->count(3)->create(['status' => TaskStatus::Pending]);

        $dto = new BulkAssignTaskDTO(
            taskUuids: $tasks->pluck('uuid')->toArray(),
            assigneeId: $assignee->id,
            institutionId: $this->institution->id,
        );

        $count = $this->service->bulkAssign($dto, $this->creator);

        expect($count)->toBe(3);
        Event::assertDispatched(TaskBulkAssignedEvent::class);
    });

    it('rejects bulk-assign when tasks belong to a different institution', function () {
        $otherInstitution = Institution::factory()->create();
        $otherTask        = Task::factory()->for($otherInstitution)->create();
        $assignee         = User::factory()->for($this->institution)->create();

        $dto = new BulkAssignTaskDTO(
            taskUuids: [$otherTask->uuid],
            assigneeId: $assignee->id,
            institutionId: $this->institution->id,
        );

        expect(fn () => $this->service->bulkAssign($dto, $this->creator))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('cancels a task and sets status to Cancelled', function () {
        $task = Task::factory()->for($this->institution)->create([
            'status' => TaskStatus::Pending,
        ]);

        $cancelled = $this->service->cancel($task, $this->creator);

        expect($cancelled->status)->toBe(TaskStatus::Cancelled);
    });

});
