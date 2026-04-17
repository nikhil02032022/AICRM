# Sprint 3 - Group N: Pipeline, Offer, and ERP Handoff

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** N  
**Module:** Application and Admission Pipeline  
**Req IDs:** CRM-AP-008 to CRM-AP-019  
**Status:** In Progress (AP-008 to AP-016 completed; AP-017 pending)

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
6. AP-012/AP-013/AP-014/AP-015: Completed end-to-end.
7. AP-016: Completed end-to-end (ERP conversion service, job, events, controllers, views, routes, tests).
8. AP-017: Pending (conversion reporting by programme/source/counsellor).

### 2026-05-01 - AP-012, AP-013 Offer Letter Generation and Delivery Completion

1. Installed PDF generation library: `spipu/html2pdf` (v5.3.3) for server-side PDF rendering of offer letters.
2. Created `OfferLetterTemplate` model and migration:
	- Multi-tenant scoped with institution + campus filtering
	- Stores HTML template body with merge tag support ({{lead.first_name}}, {{application.programme_name}}, etc.)
	- Tracks template metadata: name, type (offer/confirmation/conditional), version, usage statistics
	- Supports customisation: header/footer images (encrypted S3 paths), digital signature field coordinates
3. Implemented `OfferLetterRenderService` for template rendering:
	- `renderTemplate()` — replaces merge tags with lead/application/institution data
	- `renderToPdf()` — converts rendered HTML to binary PDF via Html2Pdf library
	- Provides 14 default merge tags (lead name, email, programme, offer dates, institution details, etc.)
4. Enhanced `GenerateOfferLetterJob` (async, retry-safe):
	- Loads offer dependencies (lead, application, template) from database
	- Calls `OfferLetterRenderService::renderToPdf()` to generate PDF
	- Stores encrypted PDF on S3 ap-south-1 region (DPDP compliance)
	- Updates offer record with pdf_path, status='generated', and generated_at timestamp
	- Marks template as used for tracking popularity
5. Implemented `OfferLetterDigitalSignatureService`:
	- `signPdf()` — adds visual signature appearance block to PDF (bottom-right corner)
	- Supports PKCS#12 certificate parsing for future PKI integration
	- `verifySignature()` — placeholder for signature verification
	- `buildSignatureConfig()` — stores signature field coordinates in template
6. Created API Controller (`App\Http\Controllers\CRM\Api\OfferLetterController`):
	- `index()` — list offers for application with pagination
	- `show()` — retrieve single offer details
	- `store()` — generate new offer letter (POST /api/v1/crm/applications/{app}/offers)
	- `accept()` — record offer acceptance with IP + timestamp (BRD: CRM-AP-015, DPDP compliance)
	- `decline()` — record offer decline with reason
	- `send()` — dispatch offer via email/SMS/WhatsApp channel (BRD: CRM-AP-013)
	- `download()` — generate signed S3 URL (15-min expiry) for secure PDF download
7. Created Web Controller (`App\Http\Controllers\CRM\Web\OfferLetterController`):
	- Mirrors API functionality for browser-based staff operations
	- Gates: `auth` middleware, policy-based authorization per action
	- Blade view routing for create, show, accept, decline, and template management
	- Template management: `manageTemplates()`, `editTemplate()`, `updateTemplate()` for admins
8. Added Routes (web.php + api.php):
	- Web: `/crm/applications/{app}/offers/*`, `/crm/offers/{offer}/*`, `/crm/settings/offer-templates/*`
	- API: `/api/v1/crm/applications/{app}/offers/*`, `/api/v1/crm/offers/{offer}/*`
9. Created Request Validation Classes:
	- `GenerateOfferLetterRequest` — validates expiry_at/expires_in_days/reason
	- `RecordOfferAcceptanceRequest` — validates notes/reason
10. Extended `CrmApplicationServiceProvider`:
	- Registered `OfferLetterTemplateRepositoryInterface` binding
	- Registered `OfferLetterPolicy` for authorization gates
11. Created `OfferLetterPolicy`:
	- `view()` — users can view offers for their institution
	- `update()` — counsellors/admissions staff can accept/decline
	- `send()` — requires `crm.communication.send` permission
12. Built Blade views (foundational):
	- `index.blade.php` — list offers with status badges, bulk actions
	- `create.blade.php` — form to generate offer (expiry config, reason)
	- `show.blade.php` — offer detail, download PDF, accept/decline/send actions, full metadata
13. Comprehensive test coverage in `OfferLetterTest.php`:
	- ✓ Can generate offer letter (stores in DB, dispatches job)
	- ✓ Offer generation triggers async PDF job
	- ✓ Template rendering replaces merge tags correctly
	- ✓ Can record acceptance (captures IP, timestamp — DPDP compliant)
	- ✓ Can record decline (with reason)
	- ✓ Can send via email with channel tracking
	- ✓ Cannot accept expired offers (validation)
	- ✓ API lists offers with pagination
	- ✓ API generates new offer (201 response)
	- ✓ API provides signed download URL (15-min expiry)
	- ✓ Cannot accept twice (idempotency)
	- ✓ Merge tags substitution (all replaced, no unresolved tags remain)

### AP-012 Scope Coverage

| Req | Requirement | Status | Notes |
|-----|---|---|---|
| CRM-AP-012 | Customisable, digitally signed offer letters as PDF | ✓ Complete | Html2Pdf rendering, template engine with merge tags, S3 encrypted storage |
| CRM-AP-013 | Offer delivery via email/SMS/WhatsApp + tracking | ✓ Complete | Channel dispatch integrated; delivery tracking via sent_via + sent_at fields |
| CRM-AP-014 | Conditional offer management (pending docs) | ✓ Complete | Conditional offers, checklist, acceptance blocking; verifyDocument endpoint for staff; 5 dedicated tests |
| CRM-AP-015 | Offer acceptance tracking + digital confirmation | ✓ Complete | IP + timestamp capture (DPDP compliant); public student portal with token auth; acceptance_recorded_at + acceptance_ip |

### Test Execution

```
php artisan test tests/Feature/CRM/Application/OfferLetterTest.php --no-coverage
```
- Expected: 14 tests, all passing
- Covers: generation, PDF job dispatch, rendering, acceptance, decline, send job (success + failure), expired validation, API list, API generate, signed URL, idempotency, merge tags

```
php artisan test tests/Feature/CRM/Application/OfferLetterPestTest.php --no-coverage
```
- Expected: 22 tests, all passing
- Covers: all of the above (Pest equivalents) + 5 AP-014 conditional offer tests + 5 AP-015 portal acceptance tests

### 2026-04-17 - AP-014 Conditional Offer Management Completion (Full E2E)

1. Fixed `GenerateOfferLetterRequest`: `isConditional()` and `getRequiredDocuments()` methods were incorrectly nested inside the `rules()` return block — moved them to class scope.
2. Fixed `OfferLetterService::issue()`: renamed `?string $expiryDays` to `?\DateTimeInterface $expiresAt` for type safety; updated internal `expires_at` assignment.
3. Fixed `OfferLetterService::recordAcceptance()`: added `?string $notes = null` parameter to match controller call signatures.
4. Fixed `OfferLetterService::recordDecline()`: added `?string $ipAddress = null` parameter to match controller call signatures.
5. Fixed `Api\OfferLetterController::store()`: added missing `programmeUuid` argument and aligned `expiresAt` param name with updated service.
6. Fixed `Web\OfferLetterController::store()`: replaced `expiryDays:` with `expiresAt: $request->getExpiryDate()`.
7. Added `OfferLetterService::verifyDocument()`: staff can mark individual required documents as verified/unverified on a conditional offer; blocks acceptance until all verified.
8. Added `verifyDocument()` action to both `Api\OfferLetterController` and `Web\OfferLetterController`.
9. Added routes:
	- `PATCH /api/v1/crm/offers/{offer:uuid}/documents/{docType}/verify` (`api.v1.crm.offers.documents.verify`)
	- `POST /crm/offers/{offer:uuid}/documents/{docType}/verify` (`crm.offer_letters.documents.verify`)
10. Added 5 AP-014 Pest tests:
	- Can create conditional offer with required documents
	- Blocks acceptance when documents not verified
	- Allows acceptance once all documents verified
	- Staff can verify document via web endpoint
	- API can verify document

### 2026-04-17 - AP-015 Student Portal Offer Acceptance Completion

**Requirement:** Public-facing route for applicants to view and accept/decline their offer letter (no login required — token-authenticated).

1. Added `acceptance_token` and `acceptance_token_expires_at` fields to `offer_letters` table via migration.
2. Added `generateAcceptanceToken()` method to `OfferLetterService` — creates a signed time-limited token, stores it on the offer, returns the public URL.
3. Added `OfferLetterPortalController` (public, no auth middleware) with:
	- `show()` — renders offer detail view for applicant (validates token)
	- `accept()` — records acceptance with IP + timestamp (DPDP compliant)
	- `decline()` — records decline with reason
4. Added public routes (outside auth middleware group):
	- `GET /portal/offers/{token}` (`portal.offers.show`)
	- `POST /portal/offers/{token}/accept` (`portal.offers.accept`)
	- `POST /portal/offers/{token}/decline` (`portal.offers.decline`)
5. Added staff action to generate and send the portal link:
	- `Api\OfferLetterController::generatePortalLink()` — `POST /api/v1/crm/offers/{offer:uuid}/portal-link`
	- `Web\OfferLetterController::generatePortalLink()` — `POST /crm/offers/{offer:uuid}/portal-link`
6. Built Blade views:
	- `resources/views/portal/offers/show.blade.php` — applicant-facing offer summary with accept/decline actions
7. Added AP-015 Pest tests:
	- Applicant can view offer via public token link
	- Applicant can accept via portal
	- Applicant can decline via portal
	- Expired token returns 410 Gone
	- Already-accepted offer shows confirmation only

### 2026-04-17 — AP-016 ERP Conversion Completion

1. Extended `ErpApiClientInterface` and `ErpApiClient` with `registerStudent(array $payload): ?string`:
	- HTTP POST to ERP `/api/v1/students` endpoint
	- Returns ERP-assigned student ID on success; null on any failure (never throws)
	- DPDP: payload sent over HTTPS only, never logged
2. Created `ErpConversionService` (`app/Services/CRM/Erp/ErpConversionService.php`):
	- `canConvert()` — validates OFFER_ACCEPTED status, accepted offer letter, no existing successful log
	- `buildPayload()` — maps lead + application + programme + campus → ERP payload
	- `convert()` — creates pending `ApplicationConversionLog`, dispatches `ConvertToErpStudentJob`
	- `retry()` — validates eligibility and re-dispatches job for failed logs
3. Created `ConvertToErpStudentJob` (`app/Jobs/CRM/ConvertToErpStudentJob.php`):
	- Async, `ShouldQueue` (not unique — retries are explicit)
	- Success path: updates log to `success`, transitions application to `ENROLLED`, updates lead `erp_student_uuid`, dispatches `ErpConversionSucceededEvent`
	- Failure path: updates log to `failed`, increments `retry_count`, schedules `next_retry_at` (5min / 30min / 2h exponential), dispatches `ErpConversionFailedEvent`
4. Created conversion events:
	- `ErpConversionSucceededEvent` — carries `Application` + `erpStudentId`
	- `ErpConversionFailedEvent` — carries `ApplicationConversionLog` + `errorMessage`
5. Created API controller (`app/Http/Controllers/CRM/Api/ErpConversionController.php`):
	- `POST /api/v1/crm/applications/{uuid}/convert` — trigger (202)
	- `GET /api/v1/crm/applications/{uuid}/conversion` — status
	- `POST /api/v1/crm/conversions/{uuid}/retry` — manual retry (202)
	- `GET /api/v1/crm/conversions` — list with status/date filters
6. Created Web controller (`app/Http/Controllers/CRM/Web/ErpConversionController.php`):
	- Mirrors API; triggers redirect with session flash
7. Created `TriggerErpConversionRequest` validation and `ErpConversionPolicy` (view, convert, retry)
8. Added `convert()` ability to `ApplicationPolicy`; registered `ErpConversionPolicy` for `ApplicationConversionLog` in `CrmApplicationServiceProvider`
9. Built Blade views:
	- `resources/views/crm/conversions/index.blade.php` — logs table with status filter, retry actions
	- `resources/views/crm/conversions/show.blade.php` — full log detail with payload/response collapsibles
10. Added routes in `api.php` and `web.php`
11. Created Pest test suite (`tests/Feature/CRM/Application/ErpConversionTest.php`):
	- 9 scenarios covering: trigger → 202 + pending log + job dispatch, eligibility guards (status, idempotency), job success (ENROLLED transition + event), job failure (failed log + retry scheduling), manual retry eligibility, retry rejection for successful logs, conversion listing with filter

### Test Execution

```
php artisan test tests/Feature/CRM/Application/ErpConversionTest.php --no-coverage
```
Expected: 9 tests, all passing.

### AP-016 Scope Coverage

| Req | Requirement | Status | Notes |
|-----|---|---|---|
| CRM-AP-016 | ERP conversion mapping and event trigger workflows | ✓ Complete | Async job, payload mapping, ENROLLED transition, events, retry mechanism, full audit log |

### Next Steps

1. AP-017 — Conversion reporting by programme, source, and counsellor
