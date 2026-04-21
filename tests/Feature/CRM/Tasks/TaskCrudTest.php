<?php

declare(strict_types=1);

// BRD: CRM-TF-001 — Task CRUD feature tests (multi-tenancy + RBAC)

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Enums\CRM\Tasks\TaskType;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Task CRUD (CRM-TF-001)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->lead        = Lead::factory()->for($this->institution)->create();
        $this->counsellor  = User::factory()
            ->for($this->institution)
            ->withPermission('crm.tasks.create')
            ->withPermission('crm.tasks.index')
            ->create();
    });

    it('counsellor can create a task linked to their assigned lead', function () {
        Sanctum::actingAs($this->counsellor, ['*']);

        $response = $this->postJson('/api/v1/crm/tasks', [
            'lead_id'     => $this->lead->id,
            'type'        => TaskType::Call->value,
            'priority'    => TaskPriority::Normal->value,
            'title'       => 'Initial call',
            'description' => null,
            'due_at'      => now()->addDay()->toIso8601String(),
            'assigned_to' => $this->counsellor->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Initial call')
            ->assertJsonPath('data.status', TaskStatus::Pending->value);
    });

    it('counsellor cannot view another institution task (404 not 403)', function () {
        $otherInstitution = Institution::factory()->create();
        $otherTask        = Task::factory()->for($otherInstitution)->create();

        Sanctum::actingAs($this->counsellor, ['*']);

        $this->getJson("/api/v1/crm/tasks/{$otherTask->uuid}")
            ->assertNotFound();
    });

    it('unauthenticated request returns 401', function () {
        $this->getJson('/api/v1/crm/tasks')
            ->assertUnauthorized();
    });

    it('manager can view all team tasks via API', function () {
        $manager = User::factory()
            ->for($this->institution)
            ->withPermission('crm.tasks.index')
            ->withPermission('crm.tasks.team.view')
            ->create();

        Task::factory()->for($this->institution)->count(3)->create();

        Sanctum::actingAs($manager, ['*']);

        $this->getJson('/api/v1/crm/tasks')
            ->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
    });

});
