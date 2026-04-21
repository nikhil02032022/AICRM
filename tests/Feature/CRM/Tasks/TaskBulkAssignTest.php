<?php

declare(strict_types=1);

// BRD: CRM-TF-008 — Bulk task assignment feature tests

use App\Events\CRM\Tasks\TaskBulkAssignedEvent;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Bulk Task Assignment (CRM-TF-008)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->actor       = User::factory()
            ->for($this->institution)
            ->withPermission('crm.tasks.bulk-assign')
            ->create();
        $this->assignee    = User::factory()->for($this->institution)->create();
        $this->tasks       = Task::factory()->for($this->institution)->count(3)->create([
            'status' => TaskStatus::Pending,
        ]);
    });

    it('bulk assign moves tasks to new counsellor atomically', function () {
        Sanctum::actingAs($this->actor, ['*']);

        $this->postJson('/crm/tasks/bulk-assign', [
            'task_uuids'  => $this->tasks->pluck('uuid')->toArray(),
            'assigned_to' => $this->assignee->id,
        ])->assertRedirect();

        foreach ($this->tasks as $task) {
            expect($task->fresh()->assigned_to)->toBe($this->assignee->id);
        }
    });

    it('cross-tenant task UUIDs are rejected with 422', function () {
        $otherInstitution = Institution::factory()->create();
        $otherTask        = Task::factory()->for($otherInstitution)->create();

        Sanctum::actingAs($this->actor, ['*']);

        $this->postJson('/crm/tasks/bulk-assign', [
            'task_uuids'  => [$otherTask->uuid],
            'assigned_to' => $this->assignee->id,
        ])->assertUnprocessable();
    });

    it('TaskBulkAssignedEvent is dispatched', function () {
        Event::fake([TaskBulkAssignedEvent::class]);

        Sanctum::actingAs($this->actor, ['*']);

        $this->postJson('/crm/tasks/bulk-assign', [
            'task_uuids'  => $this->tasks->pluck('uuid')->toArray(),
            'assigned_to' => $this->assignee->id,
        ]);

        Event::assertDispatched(TaskBulkAssignedEvent::class);
    });

});
