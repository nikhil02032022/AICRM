# Sprint 4 Group R — Test Cases

**BRD Req IDs:** CRM-TF-001 to CRM-TF-009  
**Generated:** 2026-04-21  
**Total Test Cases:** 30

---

## Unit Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-R-U-001 | TF-001 | TaskService creates task and fires TaskCreatedEvent | Task created with Pending status, event dispatched | TaskServiceTest |
| TC-R-U-002 | TF-001 | TaskService fires TaskAssignedEvent when assignee differs from creator | TaskAssignedEvent dispatched | TaskServiceTest |
| TC-R-U-003 | TF-001 | TaskService does NOT fire TaskAssignedEvent when assignee is creator | TaskAssignedEvent NOT dispatched | TaskServiceTest |
| TC-R-U-004 | TF-005 | TaskService throws when completing task without disposition | InvalidArgumentException thrown | TaskServiceTest |
| TC-R-U-005 | TF-005 | TaskService completes task and sets completed_at | Status=Completed, completed_at set, event dispatched | TaskServiceTest |
| TC-R-U-006 | TF-008 | TaskService bulk-assigns tasks and fires TaskBulkAssignedEvent | Count=3, event dispatched | TaskServiceTest |
| TC-R-U-007 | TF-008 | TaskService rejects bulk-assign for cross-tenant tasks | ValidationException thrown | TaskServiceTest |
| TC-R-U-008 | TF-001 | TaskService cancels task and sets status to Cancelled | Status=Cancelled | TaskServiceTest |
| TC-R-U-009 | TF-002 | AutoRuleService creates auto-task when lead inactive past threshold | Task created with source=Auto | TaskAutoRuleServiceTest |
| TC-R-U-010 | TF-002 | AutoRuleService skips duplicate auto-task within 24h | Returns null, no duplicate task | TaskAutoRuleServiceTest |
| TC-R-U-011 | TF-002 | AutoRuleService assigns task to lead owner for lead_owner strategy | assigned_to = lead.owner.id | TaskAutoRuleServiceTest |
| TC-R-U-012 | TF-004 | EscalationService marks overdue task and sets overdue_flagged_at | Status=Overdue, overdue_flagged_at set | TaskEscalationServiceTest |
| TC-R-U-013 | TF-004 | EscalationService fires OverdueTaskAlert to task assignee | Notification sent to assignee | TaskEscalationServiceTest |
| TC-R-U-014 | TF-004 | EscalationService does not re-escalate already-flagged tasks | Count=0, no notification | TaskEscalationServiceTest |

---

## Feature Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-R-F-001 | TF-001 | Counsellor creates task via API with valid payload | 201, task in response with Pending status | TaskCrudTest |
| TC-R-F-002 | TF-001 | Counsellor cannot view cross-tenant task (404) | 404 Not Found (not 403) | TaskCrudTest |
| TC-R-F-003 | TF-001 | Unauthenticated request returns 401 | 401 Unauthorized | TaskCrudTest |
| TC-R-F-004 | TF-006 | Manager can view all team tasks via API | 200 with paginated list | TaskCrudTest |
| TC-R-F-005 | TF-005 | POST complete without disposition returns 422 | 422 with disposition validation error | TaskCompleteDispositionTest |
| TC-R-F-006 | TF-005 | POST complete with valid disposition saves record | 200, status=Completed, disposition set | TaskCompleteDispositionTest |
| TC-R-F-007 | TF-005 | completed_at is set to current timestamp on completion | completed_at not null | TaskCompleteDispositionTest |
| TC-R-F-008 | TF-005 | Activity timeline entry created on lead record on completion | Activity with type=task_completed exists | TaskCompleteDispositionTest |
| TC-R-F-009 | TF-008 | Bulk assign moves tasks to new counsellor | All tasks.assigned_to = new assignee | TaskBulkAssignTest |
| TC-R-F-010 | TF-008 | Cross-tenant task UUIDs rejected with 422 | 422 Unprocessable | TaskBulkAssignTest |
| TC-R-F-011 | TF-008 | TaskBulkAssignedEvent is dispatched on bulk assign | Event dispatched | TaskBulkAssignTest |
| TC-R-F-012 | TF-002 | AutoCreateFollowUpTaskJob creates auto-task when lead inactive past threshold | Task with source=Auto exists | TaskAutoCreateTest |
| TC-R-F-013 | TF-002 | Job does not create duplicate auto-task within 24h window | Only 1 auto-task exists | TaskAutoCreateTest |
| TC-R-F-014 | TF-002 | Job uses atomic Redis lock to prevent concurrent runs | Lock acquired successfully | TaskAutoCreateTest |
| TC-R-F-015 | TF-009 | Calendar service returns FullCalendar-compatible event objects | Events have id, title, start, end, color, extendedProps | TaskCalendarTest |
| TC-R-F-016 | TF-009 | Calendar events scoped to requesting user institution | No cross-tenant events returned | TaskCalendarTest |
| TC-R-F-017 | TF-006 | Manager sees all counsellor tasks in institution | Paginated task list returned | TeamTaskViewTest |
| TC-R-F-018 | TF-006 | Counsellor cannot access manager team view | 403 Forbidden | TeamTaskViewTest |
| TC-R-F-019 | TF-007 | Activity feed returns TASK_* activity entries for team | 200 on activity feed page | TeamTaskViewTest |

---

## Coverage Notes

- All 9 BRD requirements (TF-001 to TF-009) covered by at least 2 test cases each
- Multi-tenancy isolation verified in: TC-R-F-002, TC-R-F-010, TC-R-F-016
- DPDP compliance: no PII in log calls; test cases verify task_uuid used in activity metadata
- Queue/job idempotency verified in: TC-R-F-013, TC-R-F-014
