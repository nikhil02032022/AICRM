<?php

declare(strict_types=1);

// BRD: CRM-TF-005 — Task completion with mandatory disposition

use App\Enums\CRM\Tasks\TaskDisposition;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Activity;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Task Complete with Disposition (CRM-TF-005)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->lead        = Lead::factory()->for($this->institution)->create();
        $this->counsellor  = User::factory()
            ->for($this->institution)
            ->withPermission('crm.tasks.complete')
            ->create();
        $this->task = Task::factory()->for($this->institution)->create([
            'lead_id'    => $this->lead->id,
            'assigned_to' => $this->counsellor->id,
            'status'     => TaskStatus::Pending,
        ]);
    });

    it('POST to complete without disposition returns 422', function () {
        Sanctum::actingAs($this->counsellor, ['*']);

        $this->postJson("/api/v1/crm/tasks/{$this->task->uuid}/complete", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['disposition']);
    });

    it('POST to complete with valid disposition saves record', function () {
        Sanctum::actingAs($this->counsellor, ['*']);

        $this->postJson("/api/v1/crm/tasks/{$this->task->uuid}/complete", [
            'disposition' => TaskDisposition::ReachedInterested->value,
            'notes'       => 'Interested in MBA programme.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::Completed->value)
            ->assertJsonPath('data.disposition', TaskDisposition::ReachedInterested->value);
    });

    it('completed_at is set to current timestamp on completion', function () {
        Sanctum::actingAs($this->counsellor, ['*']);

        $this->postJson("/api/v1/crm/tasks/{$this->task->uuid}/complete", [
            'disposition' => TaskDisposition::CallBackRequested->value,
        ])->assertOk();

        expect($this->task->fresh()->completed_at)->not->toBeNull();
    });

    it('activity timeline entry is created on lead record on completion', function () {
        Sanctum::actingAs($this->counsellor, ['*']);

        $this->postJson("/api/v1/crm/tasks/{$this->task->uuid}/complete", [
            'disposition' => TaskDisposition::MeetingScheduled->value,
        ])->assertOk();

        expect(Activity::where('subject_type', \App\Models\CRM\Lead::class)
            ->where('subject_id', $this->lead->id)
            ->where('type', 'task_completed')
            ->exists()
        )->toBeTrue();
    });

});
