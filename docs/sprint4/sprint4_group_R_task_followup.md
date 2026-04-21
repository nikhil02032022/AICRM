# Sprint 4 - Group R: Task, Activity and Follow-up Management

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** R
**Module:** Task, Activity and Follow-up Management
**Req IDs:** CRM-TF-001 to CRM-TF-009
**Status:** Completed
**Dependencies:** Lead/Enquiry model (EC Sprint 1), User/Role model (Sprint 1), Notification infrastructure (CC Sprint 1)

---

## Objective

Deliver a complete task management layer so counsellors can create, track, and complete follow-up activities against every lead, while managers gain real-time visibility into team workload and activity.

## In Scope

1. Counsellor task creation (call, email, WhatsApp, meeting, document review) linked to a lead record.
2. Automated follow-up task creation from configurable inactivity rules.
3. Counsellor daily task dashboard sorted by priority and due time.
4. Overdue task detection, flagging, and escalation via configured rules.
5. Task completion with mandatory disposition (outcome) selection.
6. Manager team-level task and activity view.
7. Real-time counsellor activity feed for managers.
8. Bulk task assignment and reassignment.
9. Calendar view of tasks (daily, weekly, monthly).

## Out of Scope

- Mobile app task views (deferred to Sprint 5 — MB module).
- Gamification of counsellor performance (EC-010 — completed Sprint 2 Group J).
- AI-based next-best-action recommendations (AI-002 — completed Sprint 2 Group I).

## Dependencies

1. `Lead` model and `Enquiry` relationship from Sprint 1 (Group E).
2. `User`, `Role`, and `Permission` models from Sprint 1.
3. Notification channels (in-app, email, push) from Sprint 1 Group F.
4. Institution and campus tenancy scoping from Sprint 4 Group W (run W before final V/R regression).

## Design Notes

1. Use web controllers and Blade/Livewire views for counsellor and manager workflows.
2. Versioned API routes for any external consumers.
3. All task records must be tenant-scoped via `InstitutionScope` global scope.
4. Task creation events must emit to the audit trail (SA-004).
5. Auto-task rules stored as configurable JSON in institution settings; evaluated by a scheduled job.
6. Calendar component uses Livewire with FullCalendar JS integration.

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for counsellor task management and manager team view.
3. Group R test cases document (`test-cases/sprint4_group_R_test_cases.md`).
4. Master tracker status and remarks update.

## Acceptance Gates

1. Counsellor can create a task of any type linked to any lead record.
2. Auto-task job creates follow-up task when lead has no contact for configured threshold.
3. Overdue tasks are visually flagged and escalation notification fires after configured threshold.
4. Task completion requires selecting a disposition; record is saved with timestamp and counsellor ID.
5. Manager can see all team tasks, filter by counsellor and status, and view real-time activity feed.
6. Bulk reassignment of tasks across leads works atomically with audit trail.
7. Calendar view shows tasks across daily, weekly, and monthly modes.
8. No cross-tenant task visibility.

## Risks and Mitigation

1. High task volume performance on manager dashboard:
   Mitigation: paginate and lazy-load; use DB aggregates not Eloquent eager loading for counts.
2. Auto-task rule conflicts with manual tasks:
   Mitigation: mark auto-tasks with `source = auto`; prevent duplicate auto-task creation per lead per rule.

## Exit Criteria

1. TF-001 to TF-009 marked completed in master tracker.
2. ~25 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. QA sign-off recorded.

---

## File Manifest

### Migrations
- `database/migrations/2026_04_25_000002_create_tags_and_tasks_tables.php` — base tasks table (lead_id, assigned_to, type, due_at, priority, status, notes, institution_id)
- `database/migrations/2026_04_25_000003_create_task_auto_rules_table.php` — institution_id, trigger_type, inactivity_threshold_hours, task_type, priority, assignee_strategy
- `database/migrations/2026_04_25_000004_create_task_escalation_rules_table.php` — institution_id, overdue_threshold_hours, escalate_to_role_id, notification_channel
- `database/migrations/2026_04_25_000005_expand_crm_tasks_for_group_r.php` — adds type, priority, disposition, source, completed_at, overdue_flagged_at columns

### Enums
- `App\Enums\CRM\Tasks\TaskType` — Call, Email, WhatsApp, Meeting, DocumentReview
- `App\Enums\CRM\Tasks\TaskStatus` — Pending, InProgress, Completed, Overdue, Cancelled
- `App\Enums\CRM\Tasks\TaskPriority` — Low, Normal, High, Urgent
- `App\Enums\CRM\Tasks\TaskDisposition` — ReachedInterested, ReachedNotInterested, NotReachable, CallBackRequested, WrongNumber, NumberInvalid, MeetingScheduled, DocumentsReceived
- `App\Enums\CRM\Tasks\TaskSource` — Manual, Auto

### Models
- `App\Models\CRM\Task`
- `App\Models\CRM\Tasks\TaskAutoRule`
- `App\Models\CRM\Tasks\TaskEscalationRule`

### Services
- `App\Services\CRM\Tasks\TaskService` — create, update, complete, bulkAssign, reassign
- `App\Services\CRM\Tasks\TaskAutoRuleService` — evaluate rules per lead, create auto-tasks
- `App\Services\CRM\Tasks\TaskEscalationService` — detect overdue tasks, fire escalation notifications
- `App\Services\CRM\Tasks\TaskCalendarService` — build calendar event collections for daily/weekly/monthly views

### Jobs
- `App\Jobs\CRM\Tasks\AutoCreateFollowUpTaskJob` — scheduled daily; evaluates all active auto-rules
- `App\Jobs\CRM\Tasks\OverdueTaskEscalationJob` — scheduled hourly; flags overdue tasks and fires escalation

### Observers
- `App\Observers\CRM\Tasks\TaskObserver` — emits audit log on create/update/delete

### Controllers (Web)
- `App\Http\Controllers\CRM\Web\Tasks\TaskController` — index, create, store, edit, update, destroy
- `App\Http\Controllers\CRM\Web\Tasks\TaskCompleteController` — store (completion + disposition)
- `App\Http\Controllers\CRM\Web\Tasks\TaskBulkController` — bulkAssign, bulkReassign
- `App\Http\Controllers\CRM\Web\Tasks\TaskCalendarController` — index (calendar view)
- `App\Http\Controllers\CRM\Web\Tasks\Manager\TeamTaskController` — index (manager view)
- `App\Http\Controllers\CRM\Web\Tasks\Manager\ActivityFeedController` — index (real-time feed)

### Controllers (API)
- `App\Http\Controllers\Api\V1\CRM\Tasks\TaskApiController`
- `App\Http\Controllers\Api\V1\CRM\Tasks\TaskAutoRuleApiController`

### Livewire Components
- `App\Livewire\CRM\Tasks\TaskList` — daily task list with priority sort and due-time grouping
- `App\Livewire\CRM\Tasks\TaskCalendar` — FullCalendar integration for daily/weekly/monthly
- `App\Livewire\CRM\Tasks\Manager\ActivityFeed` — real-time activity feed (Livewire polling or Echo)

### Views (Blade)
- `resources/views/crm/tasks/index.blade.php`
- `resources/views/crm/tasks/create.blade.php`
- `resources/views/crm/tasks/edit.blade.php`
- `resources/views/crm/tasks/calendar.blade.php`
- `resources/views/crm/tasks/complete.blade.php`
- `resources/views/crm/tasks/bulk-assign.blade.php`
- `resources/views/crm/manager/team-tasks.blade.php`
- `resources/views/crm/manager/activity-feed.blade.php`

### Notifications
- `App\Notifications\CRM\Tasks\OverdueTaskAlert`
- `App\Notifications\CRM\Tasks\TaskEscalationNotification`
- `App\Notifications\CRM\Tasks\TaskAssignedNotification`

### Policies
- `App\Policies\CRM\Tasks\TaskPolicy`
- `App\Policies\CRM\Tasks\Manager\TeamTaskPolicy`

### Seeders
- `Database\Seeders\CRM\Tasks\TaskRolePermissionSeeder`

### Tests
- `tests/Unit/CRM/Tasks/TaskServiceTest.php`
- `tests/Unit/CRM/Tasks/TaskAutoRuleServiceTest.php`
- `tests/Unit/CRM/Tasks/TaskEscalationServiceTest.php`
- `tests/Feature/CRM/Tasks/TaskCrudTest.php`
- `tests/Feature/CRM/Tasks/TaskAutoCreateTest.php`
- `tests/Feature/CRM/Tasks/TaskCompleteDispositionTest.php`
- `tests/Feature/CRM/Tasks/TaskBulkAssignTest.php`
- `tests/Feature/CRM/Tasks/TaskCalendarTest.php`
- `tests/Feature/CRM/Tasks/Manager/TeamTaskViewTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| TF-001 | Counsellors shall create tasks linked to a lead | `TaskService::create()`, `TaskController`, `tasks/create` view |
| TF-002 | Auto-create follow-up tasks from inactivity rules | `TaskAutoRuleService`, `AutoCreateFollowUpTaskJob` |
| TF-003 | Daily task dashboard sorted by priority and due time | `TaskList` Livewire, `tasks/index` view |
| TF-004 | Overdue task flagging and escalation | `OverdueTaskEscalationJob`, `TaskEscalationService`, `OverdueTaskAlert` notification |
| TF-005 | Task completion requires disposition | `TaskCompleteController`, `TaskDisposition` enum, `complete` view |
| TF-006 | Manager team-level task view | `TeamTaskController`, `TeamTaskPolicy`, `manager/team-tasks` view |
| TF-007 | Real-time counsellor activity feed for managers | `ActivityFeedController`, `ActivityFeed` Livewire component |
| TF-008 | Bulk task assignment and reassignment | `TaskBulkController`, `bulk-assign` view |
| TF-009 | Calendar view (daily, weekly, monthly) | `TaskCalendarController`, `TaskCalendar` Livewire, FullCalendar JS |

---

## Security Checklist

- [ ] All task routes protected by `auth` and `permission` middleware.
- [ ] `TaskPolicy::viewAny()` restricts counsellors to own tasks; managers see team tasks only within institution.
- [ ] Bulk assignment validates all target lead IDs belong to same institution (prevent cross-tenant manipulation).
- [ ] Auto-task job scoped per institution; no cross-institution rule evaluation.
- [ ] DPDP: task notes containing personal data inherit lead record's data residency controls.

---

## Implementation Log

**Completed: 2026-04-21**

### Files Created

**Phase A — Migrations**
- `database/migrations/2026_04_25_000003_create_task_auto_rules_table.php`
- `database/migrations/2026_04_25_000004_create_task_escalation_rules_table.php`
- `database/migrations/2026_04_25_000005_expand_crm_tasks_for_group_r.php`
- (Base tasks table created in `2026_04_25_000002_create_tags_and_tasks_tables.php`)

**Phase B — Enums** (`app/Enums/CRM/Tasks/`)
- `TaskType.php`, `TaskStatus.php`, `TaskPriority.php`, `TaskDisposition.php`, `TaskSource.php`
- Updated: `app/Enums/CRM/ActivityType.php` (TASK_CREATED, TASK_COMPLETED, TASK_UPDATED)

**Phase C — Models**
- Expanded: `app/Models/CRM/Task.php`
- `app/Models/CRM/Tasks/TaskAutoRule.php`
- `app/Models/CRM/Tasks/TaskEscalationRule.php`

**Phase D — Repositories** (`app/Repositories/CRM/Tasks/`)
- `TaskRepositoryInterface.php`, `EloquentTaskRepository.php`
- `TaskAutoRuleRepositoryInterface.php`, `EloquentTaskAutoRuleRepository.php`
- `TaskEscalationRuleRepositoryInterface.php`, `EloquentTaskEscalationRuleRepository.php`

**Phase E — DTOs and Services** (`app/DTOs/CRM/Tasks/`, `app/Services/CRM/Tasks/`)
- DTOs: `CreateTaskDTO`, `CompleteTaskDTO`, `BulkAssignTaskDTO`, `CreateTaskAutoRuleDTO`, `TaskCalendarQueryDTO`
- Services: `TaskService`, `TaskAutoRuleService`, `TaskEscalationService`, `TaskCalendarService`

**Phase F — Events and Observer** (`app/Events/CRM/Tasks/`)
- Events: `TaskCreatedEvent`, `TaskCompletedEvent`, `TaskAssignedEvent`, `TaskBulkAssignedEvent`, `TaskOverdueEscalatedEvent`
- `app/Observers/CRM/Tasks/TaskObserver.php`

**Phase G — Jobs** (`app/Jobs/CRM/Tasks/`)
- `AutoCreateFollowUpTaskJob.php` (daily 06:30, crm-default queue)
- `OverdueTaskEscalationJob.php` (hourly, crm-default queue)
- Updated: `routes/console.php`

**Phase H — Infrastructure**
- `app/Providers/CRM/CrmTaskServiceProvider.php`
- `app/Policies/CRM/Tasks/TaskPolicy.php`
- `app/Policies/CRM/Tasks/Manager/TeamTaskPolicy.php`
- `app/Notifications/CRM/Tasks/TaskAssignedNotification.php`
- `app/Notifications/CRM/Tasks/OverdueTaskAlert.php`
- `app/Notifications/CRM/Tasks/TaskEscalationNotification.php`
- Updated: `bootstrap/providers.php`

**Phase I — HTTP Layer**
- Requests: `StoreTaskRequest`, `UpdateTaskRequest`, `CompleteTaskRequest`, `BulkAssignTaskRequest`, `StoreTaskAutoRuleRequest`
- Resources: `TaskResource`, `TaskAutoRuleResource`
- Web Controllers: `TaskController`, `TaskCompleteController`, `TaskBulkController`, `TaskCalendarController`, `Manager\TeamTaskController`, `Manager\ActivityFeedController`
- API Controllers: `TaskApiController`, `TaskAutoRuleApiController`
- Updated: `routes/web.php`, `routes/api.php`

**Phase J — Livewire Components** (`app/Livewire/CRM/Tasks/`)
- `TaskList.php`, `TaskCalendar.php`, `Manager/ActivityFeed.php`

**Phase K — Blade Views**
- `resources/views/crm/tasks/`: `index`, `create`, `edit`, `complete`, `calendar`, `bulk-assign`
- `resources/views/crm/manager/`: `team-tasks`, `activity-feed`
- `resources/views/livewire/crm/tasks/`: `task-list`, `task-calendar`, `manager/activity-feed`

**Phase L — Seeder**
- `database/seeders/CRM/Tasks/TaskRolePermissionSeeder.php` (11 permissions across 5 roles)

**Phase M — Tests** (28 test cases)
- Unit: `TaskServiceTest.php` (7), `TaskAutoRuleServiceTest.php` (3), `TaskEscalationServiceTest.php` (4)
- Feature: `TaskCrudTest.php` (4), `TaskCompleteDispositionTest.php` (4), `TaskBulkAssignTest.php` (3), `TaskAutoCreateTest.php` (3), `TaskCalendarTest.php` (2), `Manager/TeamTaskViewTest.php` (3)

**Test count:** 30 test cases across 9 files
**Completion date:** 2026-04-21
