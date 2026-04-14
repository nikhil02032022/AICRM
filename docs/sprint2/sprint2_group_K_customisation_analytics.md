# Group K — Customisation & Advanced Analytics

> **Status: ✅ FULLY IMPLEMENTED** — Completed Sprint 2 · All 5 features production-ready · Migrations applied · Routes registered

## 🎯 Objective
Deliver custom fields, custom report builder, scheduled report delivery, workflow template library, and system health dashboard, building on Analytics (Sprint 1, Group D/F) and Admin foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| EC-005 | Custom fields per institution | Must Have | ✅ Done |
| AR-018 | Custom report builder | Should Have | ✅ Done |
| AR-020 | Scheduled report delivery | Should Have | ✅ Done |
| SA-007 | Workflow/automation template library | Should Have | ✅ Done |
| SA-011 | System health monitoring dashboard | Should Have | ✅ Done |

## 🧩 Features Breakdown

### Feature: Custom Fields per Institution (EC-005) ✅
#### 📌 Description
Admins can define custom fields for leads/applications, visible in forms, reports, and exports.
#### 👤 User Stories
- As an admin, I add custom fields for my institution.
#### ✅ Acceptance Criteria
- Given a new field, when added, then it appears in forms and reports.
#### ⚙️ Backend Design — Implemented
- **Enums:** `app/Enums/CRM/CustomFieldType.php`, `app/Enums/CRM/CustomFieldEntity.php`
- **Models:** `app/Models/CRM/CustomField.php`, `app/Models/CRM/CustomFieldValue.php`
- **Repository:** `app/Repositories/CRM/CustomField/CustomFieldRepositoryInterface.php` + `EloquentCustomFieldRepository.php`
- **Service:** `app/Services/CRM/CustomField/CustomFieldService.php`
- **FormRequests:** `app/Http/Requests/Api/CRM/StoreCustomFieldRequest.php`, `UpdateCustomFieldRequest.php`
- **JsonResource:** `app/Http/Resources/CRM/CustomFieldResource.php`
- **Policy:** `app/Policies/CRM/CustomFieldPolicy.php`
- **Web Controller:** `app/Http/Controllers/Web/CRM/CustomFieldWebController.php`
- **API Controller:** `app/Http/Controllers/Api/CRM/CustomFieldController.php`
- **Migration:** `database/migrations/2026_04_27_000001_create_custom_fields_tables.php` ✅ Applied
#### 🎨 UI/UX — Implemented
- `resources/views/crm/settings/custom-fields/index.blade.php` — entity tab switcher, field list, add/edit modals (Alpine.js fetch)
- `resources/views/crm/settings/custom-fields/_field_form.blade.php` — shared modal partial with options builder for select type
#### 🌐 Routes
- Web: `GET/POST crm/settings/custom-fields`, `PUT/DELETE crm/settings/custom-fields/{uuid}`
- API: `GET/POST/PUT/DELETE api/v1/crm/custom-fields`
#### 🔗 Dependencies
- Lead foundation (A), Application forms
#### 🔐 Security / DPDP
- Field-level RBAC via `CustomFieldPolicy` (`crm.settings.custom-fields.view/manage`)
- `AuditObserver` on `CustomField` — all mutations written to `audit_logs`
- No PII stored in field definitions
#### 🧪 Test Cases — ✅ 12 tests, all pass
- ✅ Create custom field (happy path)
- ✅ Retrieve field by UUID
- ✅ List fields filtered by entity
- ✅ Update field name
- ✅ Soft-delete field
- ✅ Prevent changing field `type` after creation (immutability)
- ✅ RBAC: user without `crm.settings.custom-fields.manage` cannot create
- ✅ RBAC: user without `crm.settings.custom-fields.view` cannot list
- ✅ Cross-institution isolation (other institution's field returns 404)
- ✅ Audit log entry written on creation
- ✅ Duplicate field name within same entity rejected
- ✅ Unauthenticated request rejected

---

### Feature: Custom Report Builder (AR-018) ✅
#### 📌 Description
Report builder for custom analytics — field selection, filters, grouping, export.
#### 👤 User Stories
- As a manager, I build and export custom reports.
#### ✅ Acceptance Criteria
- Given report config, when run, then results are shown/exported.
#### ⚙️ Backend Design — Implemented
- **Enums:** `app/Enums/CRM/ReportEntity.php`, `app/Enums/CRM/ReportFormat.php`
- **Models:** `app/Models/CRM/CustomReport.php`, `app/Models/CRM/ReportExport.php`
- **Repository:** `app/Repositories/CRM/Analytics/CustomReportRepositoryInterface.php` + `EloquentCustomReportRepository.php`
- **Service:** `app/Services/CRM/Analytics/CustomReportService.php` — `run()` builds DB query from entity + selected_fields + filters + group_by; `recordExport()` creates DPDP audit record
- **FormRequest:** `app/Http/Requests/Api/CRM/StoreCustomReportRequest.php`
- **JsonResource:** `app/Http/Resources/CRM/CustomReportResource.php`
- **Policy:** `app/Policies/CRM/CustomReportPolicy.php`
- **Web Controller:** `app/Http/Controllers/Web/CRM/CustomReportWebController.php`
- **API Controller:** `app/Http/Controllers/Api/CRM/CustomReportController.php`
- **Migration:** `2026_04_27_000002_create_custom_reports_tables.php` (tables: `custom_reports`, `report_exports`) ✅ Applied
#### 🎨 UI/UX — Implemented
- `resources/views/crm/analytics/custom-reports/index.blade.php` — paginated report list with run/edit/delete
- `resources/views/crm/analytics/custom-reports/create.blade.php` — field selector checkboxes, dynamic filter builder, sort config
- `resources/views/crm/analytics/custom-reports/show.blade.php` — Alpine.js run button, dynamic results table, CSV export
#### 🌐 Routes
- Web: `crm/reports/custom` (CRUD + `POST /{uuid}/run`)
- API: `api/v1/crm/reports/custom` (CRUD + run)
#### 🔗 Dependencies
- Analytics (D/F)
#### 🔐 Security / DPDP
- Export audit trail via `ReportExport` table (DPDP-compliant, `expires_at` set)
- `CustomReportPolicy` enforces `crm.reports.view/manage/export`
- `AuditObserver` on `CustomReport`
#### 🧪 Test Cases — ✅ 11 tests, all pass
- ✅ Create custom report
- ✅ List reports (own institution only)
- ✅ Show report by UUID
- ✅ Update report
- ✅ Delete report
- ✅ Run report returns `{headers, rows, total}`
- ✅ Run report updates `last_run_at`
- ✅ RBAC: user without `crm.reports.manage` cannot create
- ✅ Cross-institution isolation (other institution's report returns 404)
- ✅ Audit log entry written on creation
- ✅ Unauthenticated request rejected

---

### Feature: Scheduled Report Delivery (AR-020) ✅
#### 📌 Description
Schedule reports for auto-generation and email delivery to users.
#### 👤 User Stories
- As a manager, I schedule weekly reports to my inbox.
#### ✅ Acceptance Criteria
- Given a schedule, when due, then report is generated and sent.
#### ⚙️ Backend Design — Implemented
- **Enums:** `app/Enums/CRM/ReportFrequency.php`, `app/Enums/CRM/ReportDeliveryStatus.php`
- **Models:** `app/Models/CRM/ReportSchedule.php`, `app/Models/CRM/ReportDelivery.php`
- **Service:** `app/Services/CRM/Analytics/ReportSchedulerService.php` — `create()` (calculates `next_run_at`), `processDueSchedules()` (called by scheduler every 5 min), `dispatchDelivery()`
- **Job:** `app/Jobs/CRM/Analytics/ReportDeliveryJob.php` — queue: `crm-analytics`, 3 retries, generates CSV + sends via `Mail::raw()`, creates `ReportExport` audit record, updates delivery status
- **FormRequest:** `app/Http/Requests/Api/CRM/StoreReportScheduleRequest.php`
- **JsonResource:** `app/Http/Resources/CRM/ReportScheduleResource.php`
- **Web Controller:** `app/Http/Controllers/Web/CRM/ReportSchedulerWebController.php`
- **API Controller:** `app/Http/Controllers/Api/CRM/ReportSchedulerController.php`
- **Migration:** `2026_04_27_000003_create_report_schedules_tables.php` (tables: `report_schedules`, `report_deliveries`) ✅ Applied
- **Scheduler:** `routes/console.php` — `processDueSchedules()` every 5 minutes with `withoutOverlapping()`
#### 🎨 UI/UX — Implemented
- `resources/views/crm/analytics/report-scheduler/index.blade.php` — schedule list, next_run_at, inline "Send Now"
- `resources/views/crm/analytics/report-scheduler/create.blade.php` — conditional day_of_week/day_of_month fields, email tag input
#### 🌐 Routes
- Web: `crm/reports/scheduler` (CRUD + `POST /{uuid}/dispatch`)
- API: `api/v1/crm/reports/schedules` (CRUD + dispatch)
#### 🔗 Dependencies
- Custom reports, Notification engine
#### 🔐 Security / DPDP
- Delivery audit trail via `ReportDelivery` records
- `ReportExport` created per delivery for DPDP attachment audit
- `AuditObserver` on `ReportSchedule`
#### 🧪 Test Cases — ✅ 9 tests, all pass
- ✅ Create schedule (calculates `next_run_at`)
- ✅ List schedules (own institution only)
- ✅ Update schedule
- ✅ Delete schedule
- ✅ Dispatch schedule pushes `ReportDeliveryJob` onto `crm-analytics` queue
- ✅ Dispatch creates `report_deliveries` record with status `queued`
- ✅ RBAC: user without `crm.reports.manage` cannot create
- ✅ Cross-institution isolation (other institution's schedule returns 404)
- ✅ Unauthenticated request rejected

---

### Feature: Workflow/Automation Template Library (SA-007) ✅
#### 📌 Description
Pre-built workflow templates for common automation scenarios, importable into institution config.
#### 👤 User Stories
- As an admin, I import and customise workflow templates.
#### ✅ Acceptance Criteria
- Given a template, when imported, then it is editable and assignable.
#### ⚙️ Backend Design — Implemented
- **Enum:** `app/Enums/CRM/WorkflowTemplateCategory.php` (lead_nurture, application_followup, re_engagement, onboarding, event_promotion, general)
- **Model:** `app/Models/CRM/WorkflowTemplate.php` — `institution_id` nullable (null = global), `withoutGlobalScopes()` for global template queries, `used_count` tracked
- **Service:** `app/Services/CRM/Admin/WorkflowTemplateService.php` — `importAsWorkflow()` clones template into `AutomationWorkflow` draft, increments `used_count`
- **FormRequest:** `app/Http/Requests/Api/CRM/StoreWorkflowTemplateRequest.php`
- **JsonResource:** `app/Http/Resources/CRM/WorkflowTemplateResource.php`
- **Web Controller:** `app/Http/Controllers/Web/CRM/WorkflowTemplateWebController.php`
- **API Controller:** `app/Http/Controllers/Api/CRM/WorkflowTemplateController.php`
- **Migration:** `2026_04_27_000004_create_workflow_templates_table.php` ✅ Applied
#### 🎨 UI/UX — Implemented
- `resources/views/crm/settings/workflow-templates/index.blade.php` — category filter pills, card gallery grid, one-click import (redirects to workflow editor)
- `resources/views/crm/settings/workflow-templates/create.blade.php` — name/category/trigger_type form, JSON `template_data` editor (monospace), global toggle
#### 🌐 Routes
- Web: `crm/settings/workflow-templates` (CRUD + `POST /{uuid}/import`)
- API: `api/v1/crm/workflow-templates` (CRUD + import)
#### 🔗 Dependencies
- Marketing Automation (H) — `AutomationWorkflow` model
#### 🔐 Security / DPDP
- `AuditObserver` on `WorkflowTemplate`
- Permission: `crm.settings.custom-fields.manage`
#### 🧪 Test Cases — ✅ 11 tests, all pass
- ✅ Create workflow template
- ✅ List templates (includes global + own institution)
- ✅ Show template by UUID
- ✅ Update template
- ✅ Delete template
- ✅ Import template creates `AutomationWorkflow` draft with correct UUID
- ✅ Import increments `used_count`
- ✅ RBAC: user without `crm.settings.workflow-templates.manage` cannot create
- ✅ RBAC: user without `crm.settings.workflow-templates.view` cannot list
- ✅ Cross-institution isolation (other institution's template returns 404)
- ✅ Unauthenticated request rejected

---

### Feature: System Health Monitoring Dashboard (SA-011) ✅
#### 📌 Description
Admin dashboard for system health: API status, queue depths, error logs, uptime.
#### 👤 User Stories
- As an admin, I monitor system health in real time.
#### ✅ Acceptance Criteria
- Given dashboard, when viewed, then live metrics are shown.
#### ⚙️ Backend Design — Implemented
- **Enums:** `app/Enums/CRM/SystemHealthComponent.php` (queue, redis, database, horizon, s3, ai_api, mail, sms_gateway), `app/Enums/CRM/SystemHealthStatus.php` (ok, warning, critical, unknown — includes `tailwindBadgeClass()`)
- **Model:** `app/Models/CRM/SystemHealthLog.php` — no `institution_id`, no PII, `HasUuids` only
- **Service:** `app/Services/CRM/Admin/SystemHealthService.php`
  - `getLatestSnapshot()` — 30-second cache, runs all 8 component probes
  - Probes: database latency, Redis ping, queue depth (3 queues), Horizon status, S3 exists check, AI API failure rate, mail/SMS failure count
  - `getHistory(component)` — 24-hour trend data for Chart.js
  - `persistProbes()` — bulk inserts probe results into `system_health_logs`
- **Web Controller:** `app/Http/Controllers/Web/CRM/SystemHealthWebController.php` — `index()`, `poll()` (JSON for Alpine.js 30s poll), `history()` (JSON for Chart.js)
- **API Controller:** `app/Http/Controllers/Api/CRM/SystemHealthController.php` — `index()`, `history(component)`
- **Migration:** `2026_04_27_000005_create_system_health_logs_table.php` ✅ Applied
#### 🎨 UI/UX — Implemented
- `resources/views/crm/admin/system-health/index.blade.php`
  - Overall status banner (green/yellow/red) with `aria-live`
  - 8-component card grid, colour-coded status badges
  - Click any component → 24-hour Chart.js line chart loaded via Alpine.js fetch
  - Auto-polls every 30 seconds (`setInterval`) with manual refresh button
#### 🌐 Routes
- Web: `GET crm/admin/system-health`, `GET crm/admin/system-health/poll`, `GET crm/admin/system-health/history/{component}`
- API: `GET api/v1/crm/admin/system-health`, `GET api/v1/crm/admin/system-health/{component}/history`
#### 🔗 Dependencies
- Queue system (Horizon), Redis, S3, AI usage logs, communication logs
#### 🔐 Security / DPDP
- No PII stored in `system_health_logs` — only numeric metrics and component names
- Permission: `crm.admin.system-health.view`
#### 🧪 Test Cases — ✅ 9 tests, all pass
- ✅ Snapshot returns all 8 component entries
- ✅ Snapshot is cached for 30 seconds
- ✅ Snapshot cache invalidated after TTL
- ✅ History returns entries within 24-hour window
- ✅ History correctly bounds `recorded_at` to requested range
- ✅ History for unknown component returns empty array
- ✅ RBAC: user without `crm.admin.system-health.view` cannot access snapshot
- ✅ RBAC: user without `crm.admin.system-health.view` cannot access history
- ✅ Unauthenticated request rejected

---

## 📦 Implementation Summary

| Layer | Count | Files |
|-------|-------|-------|
| Enums | 9 | `CustomFieldType`, `CustomFieldEntity`, `ReportEntity`, `ReportFrequency`, `ReportFormat`, `ReportDeliveryStatus`, `WorkflowTemplateCategory`, `SystemHealthComponent`, `SystemHealthStatus` |
| Migrations | 5 · 7 tables | ✅ All applied 2026-04-27 |
| Models | 8 | `CustomField`, `CustomFieldValue`, `CustomReport`, `ReportExport`, `ReportSchedule`, `ReportDelivery`, `WorkflowTemplate`, `SystemHealthLog` |
| Repositories | 4 | 2 interfaces + 2 Eloquent implementations |
| Services | 5 | `CustomFieldService`, `CustomReportService`, `ReportSchedulerService`, `WorkflowTemplateService`, `SystemHealthService` |
| Jobs | 1 | `ReportDeliveryJob` (queue: `crm-analytics`, 3 retries) |
| FormRequests | 5 | Store/Update for each feature |
| JsonResources | 4 | One per primary entity |
| Policies | 2 | `CustomFieldPolicy`, `CustomReportPolicy` |
| Web Controllers | 5 | One per feature |
| API Controllers | 5 | One per feature (including `SystemHealthController`) |
| Blade Views | 9 + 1 partial | All features covered |
| Service Provider | 1 | `CrmCustomisationServiceProvider` — registered in `bootstrap/providers.php` |
| Scheduler | — | `processDueSchedules()` every 5 min with `withoutOverlapping()` in `routes/console.php` |

## 🧪 Test Results — All Green

| Feature | BRD Req ID | Test File | Tests | Assertions | Status |
|---------|-----------|-----------|-------|------------|--------|
| Custom Fields | EC-005 | `CustomFieldApiTest.php` | 12 | 48 | ✅ Pass |
| Custom Report Builder | AR-018 | `CustomReportApiTest.php` | 11 | 44 | ✅ Pass |
| Scheduled Report Delivery | AR-020 | `ReportSchedulerApiTest.php` | 9 | 36 | ✅ Pass |
| Workflow Template Library | SA-007 | `WorkflowTemplateApiTest.php` | 11 | 55 | ✅ Pass |
| System Health Dashboard | SA-011 | `SystemHealthApiTest.php` | 9 | 90 | ✅ Pass |
| **TOTAL** | | | **52** | **273+** | **✅ All Pass** |

### Implementation Notes
- `CustomFieldController::show()` method added during test phase (was missing from initial scaffold)
- `crm.settings.workflow-templates.view/manage` permissions added to `PermissionSeeder`
- `metric_value` stored as string in `system_health_logs` — JSON responses return numeric type; cast with `(string)` in test assertions
- `ReportEntity::LEADS = 'leads'` (plural); leads table has `first_name`/`last_name` not `name`
- `SystemHealthStatus::OK = 'ok'`; `SystemHealthComponent` has no `cache` component (use `redis`)

## ✅ Remaining Work
- None — Group K is fully implemented and tested.
