<?php

declare(strict_types=1);

// BRD: CRM-TF-009 — Task calendar API tests

use App\Enums\CRM\Tasks\TaskStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Task Calendar (CRM-TF-009)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->user        = User::factory()
            ->for($this->institution)
            ->withPermission('crm.tasks.calendar')
            ->withPermission('crm.tasks.index')
            ->create();

        Task::factory()->for($this->institution)->count(3)->create([
            'assigned_to' => $this->user->id,
            'status'      => TaskStatus::Pending,
            'due_at'      => now()->addDays(2),
        ]);
    });

    it('calendar endpoint returns FullCalendar-compatible event objects', function () {
        Sanctum::actingAs($this->user, ['*']);

        $start = now()->startOfWeek()->toIso8601String();
        $end   = now()->endOfWeek()->toIso8601String();

        // Hit the Livewire component method via service directly
        $dto    = new \App\DTOs\CRM\Tasks\TaskCalendarQueryDTO(
            start: \Carbon\Carbon::parse($start),
            end: \Carbon\Carbon::parse($end),
            viewType: 'week',
        );
        $events = app(\App\Services\CRM\Tasks\TaskCalendarService::class)
            ->buildCalendarEvents($this->user, $dto);

        expect($events)->toBeArray()
            ->and(count($events))->toBeGreaterThanOrEqual(1);

        $event = $events[0];
        expect($event)->toHaveKey('id')
            ->toHaveKey('title')
            ->toHaveKey('start')
            ->toHaveKey('end')
            ->toHaveKey('color')
            ->toHaveKey('extendedProps');
    });

    it('events are scoped to requesting user institution only', function () {
        $otherInstitution = Institution::factory()->create();
        Task::factory()->for($otherInstitution)->count(2)->create([
            'due_at' => now()->addDays(2),
        ]);

        $dto    = new \App\DTOs\CRM\Tasks\TaskCalendarQueryDTO(
            start: now()->startOfWeek(),
            end: now()->endOfWeek(),
            viewType: 'week',
        );
        $events = app(\App\Services\CRM\Tasks\TaskCalendarService::class)
            ->buildCalendarEvents($this->user, $dto);

        // All returned event uuids must belong to this user's institution
        $taskUuids = collect($events)->pluck('extendedProps.taskUuid');
        $wrongTasks = Task::whereIn('uuid', $taskUuids)
            ->where('institution_id', '!=', $this->institution->id)
            ->count();

        expect($wrongTasks)->toBe(0);
    });

});
