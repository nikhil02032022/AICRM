# Sprint 3 - Group N: Pipeline, Offer, and ERP Handoff

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** N  
**Module:** Application and Admission Pipeline  
**Req IDs:** CRM-AP-008 to CRM-AP-019  
**Status:** In Progress (AP-008, AP-009, AP-010, and AP-011 completed)

---

## Objective

Deliver application pipeline operations, offer lifecycle, and lead-to-student conversion handoff readiness.

## In Scope

1. Kanban and list-based pipeline views.
2. Advanced filters and bulk actions.
3. Seat availability visibility against application counts.
4. Offer letter generation and distribution flow.
5. Offer acceptance and confirmation capture.
6. ERP conversion mapping and event trigger workflows.
7. Conversion reporting by programme, source, and counsellor.

## Dependencies

1. Group M application entity and statuses.
2. ERP integration contracts and credentials.
3. Notification and communication foundation for offer delivery.

## Design Notes

1. Maintain full status transition audit history.
2. Keep offer generation async where document rendering is heavy.
3. Use explicit conversion service boundary for ERP write operations.
4. Preserve zero re-entry principle in AP-017.

## Deliverables

1. Group implementation log updates.
2. User manual section for admissions team pipeline operations.
3. Group N test cases document.
4. Master tracker status and remarks update.

## Acceptance Gates

1. Applications can move across configured pipeline stages.
2. Offer letters are generated, delivered, and tracked.
3. Accepted applicants can be converted through validated ERP mapping.
4. Conversion metrics are visible and accurate.

## Risks and Mitigation

1. ERP dependency delays:
Mitigation: mock contract tests and retry-safe conversion jobs.
2. Pipeline drift:
Mitigation: enforce status transition rules in service layer.

## Exit Criteria

1. AP-008 to AP-019 marked completed in tracker.
2. User manual and test cases published.
3. QA and integration sign-off recorded.

---

## Implementation Log

### 2026-04-16 - AP-008 End-to-End Completion

1. Implemented AP-008 API feature coverage for pipeline listing and detail retrieval with tenancy enforcement.
2. Implemented AP-008 web feature coverage for board view, list view, detail view, and transition form route rendering.
3. Added missing AP-008 web pipeline Blade templates required by `ApplicationPipelineWebController`:
	- `resources/views/crm/applications/pipeline/board.blade.php`
	- `resources/views/crm/applications/pipeline/list.blade.php`
	- `resources/views/crm/applications/pipeline/show.blade.php`
	- `resources/views/crm/applications/pipeline/modals/transition-form.blade.php`
4. Fixed production blockers discovered during AP-008 validation:
	- Corrected `Application::currentOfferLetter()` to return a proper Eloquent relationship for eager loading.
	- Corrected transition authorization ability usage to policy ability `transition` in API controller, web controller, and Livewire board component.
	- Corrected `ApplicationStatusHistory` model primary key configuration to match migration schema (`id` auto-increment + separate `uuid` column).

### 2026-04-16 - AP-009 Web Transition Execution (Next Requirement Continuation)

1. Implemented AP-009 web transition submit action in `ApplicationPipelineWebController`:
	- Added `transition()` action to validate payload, enforce policy authorization, and execute state transition through `ApplicationPipelineService`.
	- Added validation/error handling for invalid status values and disallowed transitions.
2. Added dedicated AP-009 apply route:
	- `POST /crm/applications/{application:uuid}/transition/apply`
	- Route name: `crm.applications.transition.apply`
3. Upgraded transition form Blade view to a working AP-009 action form:
	- Displays only valid next statuses from enum state machine (`transitionsTo()`).
	- Captures optional reason and posts to apply route.
	- Handles validation errors inline.
4. Added AP-009 web feature test:
	- Verifies successful transition from `under_review` to `shortlisted`.
	- Verifies `application_status_history` audit row creation.

### 2026-04-16 - AP-009 BRD Filter Completion (Programme, Batch, Source, Status, Date, Score)

1. Aligned AP-009 implementation to BRD filter requirement by extending repository-level filters in `EloquentApplicationRepository`:
	- Added `programme_id`, `batch`, `source`, `score_min`, and `score_max` filtering logic.
	- Preserved existing `status`, `counsellor`, and date-range filters.
2. Extended API filter input support in `ApplicationPipelineController@index`:
	- Added request query mapping for `programme_id`, `batch`, `source`, `score_min`, `score_max`.
3. Implemented AP-009 filterable web list workflow in `ApplicationPipelineWebController@listView` and list Blade view:
	- Added filter form controls for programme, batch, counsellor, source, status, date range, and score bounds.
	- Added paginated applications table to validate filter behavior in web flow.
4. Added and updated feature tests for AP-009 filter requirement:
	- API test verifies filtering by programme + batch + source + score range.
	- Web test verifies list filtering returns matching applicant and excludes non-matching applicant.

### 2026-04-16 - AP-010 End-to-End Completion (Bulk Actions)

1. Implemented AP-010 API bulk actions in `ApplicationPipelineController` with dedicated endpoints:
	- `POST /api/v1/crm/applications/bulk/status`
	- `POST /api/v1/crm/applications/bulk/assign`
	- `POST /api/v1/crm/applications/bulk/communication`
	- `POST /api/v1/crm/applications/bulk/export`
2. Implemented AP-010 web bulk actions in `ApplicationPipelineWebController` and routes:
	- `POST /crm/applications/bulk/status`
	- `POST /crm/applications/bulk/assign`
	- `POST /crm/applications/bulk/communication`
	- `POST /crm/applications/bulk/export`
3. Added service-layer AP-010 orchestration in `ApplicationPipelineService`:
	- `bulkUpdateStatus()` with status transition rules and history integrity
	- `bulkAssignCounsellor()`
	- `bulkSendCommunication()` for EMAIL/SMS/WHATSAPP channel fan-out
	- `buildExportRows()` for CSV/JSON export payload generation
4. Added repository primitives for AP-010 selection/update by UUID in `EloquentApplicationRepository`:
	- `findManyByUuids()`
	- `bulkAssignCounsellorByUuids()`
5. Added AP-010 bulk action validation requests for API and web flows.
6. Extended application list UI (`resources/views/crm/applications/pipeline/list.blade.php`) with:
	- row selection controls
	- bulk status update action
	- bulk assign counsellor action
	- bulk communication action
	- bulk export action
7. Added AP-010 test coverage:
	- API: bulk status, bulk assign, bulk communication, bulk export
	- Web: bulk status, bulk assign, bulk communication, bulk export

### 2026-04-16 - AP-011 Seat Availability Visibility Completion

1. Replaced the AP-011 seat availability stub in `ApplicationPipelineService` with programme-level aggregation driven by active CRM programme capacity and live primary-programme application counts.
2. Extended CRM programme persistence for AP-011 capacity tracking:
	- Added `intake_capacity` to `crm_programmes` for local programme catalogue capacity storage pending full ERP refresh automation.
3. Upgraded the pipeline board web experience for AP-011:
	- Replaced the placeholder board page with the Livewire pipeline board component.
	- Added seat availability summary tiles and programme-wise seat cards showing capacity, application count, available seats, and utilisation state.
	- Wired counsellor filter options into the board component.
4. Expanded AP-011 verification coverage:
	- API test now verifies real computed seat metrics for a configured programme.
	- Web test verifies programme seat cards render on the pipeline board.

### Verification Evidence

1. `php artisan test tests/Feature/CRM/Api/ApplicationPipelineApiTest.php --no-coverage`
	- Result: PASS (13 tests)
2. `php artisan test tests/Feature/CRM/Application/ApplicationPipelineWebTest.php --no-coverage`
	- Result: PASS (12 tests)
3. `php artisan test tests/Feature/CRM/Api/ApplicationPipelineApiTest.php tests/Feature/CRM/Application/ApplicationPipelineWebTest.php`
	- Result: PASS (25 tests, 104 assertions)

### Scope Status

1. AP-008: Completed and verified.
2. AP-009: API and web filtering requirements implemented and verified; transition state-machine flow also implemented and verified.
3. AP-010: Completed end-to-end across API + web + tests.
4. AP-011: Completed across service + web board + API + tests.
5. AP-018/AP-019: Covered in API suite and currently passing for implemented endpoints.
6. AP-012/AP-013/AP-014/AP-015/AP-016/AP-017: Pending full Group N implementation.
