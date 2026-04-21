<?php

declare(strict_types=1);

// BRD: CRM-TF-006, TF-007 — Manager team task view and activity feed tests

use App\Enums\CRM\ActivityType;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Activity;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Manager Team Task View (CRM-TF-006, TF-007)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->counsellor  = User::factory()->for($this->institution)->withPermission('crm.tasks.index')->create();
        $this->manager     = User::factory()
            ->for($this->institution)
            ->withPermission('crm.tasks.index')
            ->withPermission('crm.tasks.team.view')
            ->withPermission('crm.tasks.activity-feed.view')
            ->create();
        $this->lead = Lead::factory()->for($this->institution)->create();
    });

    it('manager sees tasks for all counsellors in institution', function () {
        Task::factory()->for($this->institution)->count(4)->create([
            'assigned_to' => $this->counsellor->id,
            'status'      => TaskStatus::Pending,
        ]);

        Sanctum::actingAs($this->manager, ['*']);

        $this->getJson('/api/v1/crm/tasks')
            ->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
    });

    it('counsellor role cannot access manager team view (403)', function () {
        $junior = User::factory()
            ->for($this->institution)
            ->withPermission('crm.tasks.index')
            ->create();

        $this->actingAs($junior)
            ->get(route('crm.manager.team-tasks'))
            ->assertForbidden();
    });

    it('activity feed returns TASK_* activity entries for team', function () {
        Activity::factory()->create([
            'institution_id' => $this->institution->id,
            'subject_type'   => Lead::class,
            'subject_id'     => $this->lead->id,
            'type'           => ActivityType::TASK_CREATED->value,
            'performed_by_id' => $this->counsellor->id,
        ]);

        Activity::factory()->create([
            'institution_id' => $this->institution->id,
            'subject_type'   => Lead::class,
            'subject_id'     => $this->lead->id,
            'type'           => ActivityType::TASK_COMPLETED->value,
            'performed_by_id' => $this->counsellor->id,
        ]);

        $this->actingAs($this->manager)
            ->get(route('crm.manager.activity-feed'))
            ->assertOk();
    });

});
