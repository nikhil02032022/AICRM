# Sprint 4 - Group V: Analytics, Dashboards and Reporting

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** V
**Module:** Analytics, Dashboards and Reporting
**Req IDs:** CRM-AR-001 to CRM-AR-021
**Status:** ✅ Completed (2026-04-23) — All Must Have (AR-001–017, AR-019) and Should Have (AR-018, AR-020) delivered; AR-021 Could Have deferred to Sprint 5
**Dependencies:** All prior sprint models (LC, LQ, EC, CC, TC, MA, AP, FM, DM, TF, AG); Group W tenancy middleware recommended before final regression

---

## Objective

Deliver institution-wide admissions dashboards, role-scoped analytics, 9 standard reports, a custom report builder, Excel/PDF export, and scheduled report delivery — giving counsellors, managers, and directors actionable insight at every level.

## In Scope

1. Institution admissions dashboard (leads, applications, offers, enrolments, revenue — by programme, campus, source, period) — AR-001.
2. Counsellor performance dashboard (leads owned, tasks completed, conversion rate, response time) — AR-002.
3. Marketing campaign dashboard (spend vs leads, CPL, CPE, channel ROI) — AR-003.
4. Admissions funnel visualisation with stage-wise conversion and drop-off — AR-004.
5. Seat availability vs confirmed enrolments real-time view — AR-005.
6. Director/Management executive dashboard (KPI tiles and trend charts) — AR-006.
7. Role-based dashboard data scoping (counsellor sees own data; manager sees team; director sees institution) — AR-007.
8. Date range filter and drill-down to individual lead records on all dashboards — AR-008.
9. Standard Reports: Enquiry Register, Counsellor Activity, Application Status, Source Effectiveness, Lost Lead Analysis, Fee Collection, Document Compliance, Year-on-Year Comparison, Agent Performance — AR-009 to AR-017.
10. Export all reports to Excel and PDF — AR-019.
11. Custom report builder (field selection, filters, grouping, aggregations) — AR-018 (Should Have).
12. Scheduled report delivery via email — AR-020 (Should Have).
13. API access to analytics data (Power BI / Tableau) — AR-021 (Could Have).

## Out of Scope

- Raw data warehouse or ETL pipeline — AR-021 is a read-only API only.
- Real-time push-to-dashboard from external ad platforms (MA module handles that via campaign spend import).

## Dependencies

1. All CRM models from Sprints 1–4 (Lead, Enquiry, Application, Payment, Document, Task, Agent, Commission).
2. Group W tenancy service (SA-001) to scope queries per institution and campus.
3. `ReportExportService` uses `maatwebsite/excel` (Laravel Excel) and `barryvdh/laravel-dompdf`.
4. Scheduled reports: `ScheduledReportJob` uses Laravel scheduler + notification channels (email/CC-001).

## Design Notes

1. All dashboard data must be served from pre-aggregated DB views or summary tables for AR-001 performance target (< 3s page load).
2. Role-based scoping is enforced at service layer (`DashboardScopeService`) — never trust frontend-passed filters for user scope.
3. Funnel chart uses Chart.js or ApexCharts via CDN (lightweight, no build step required).
4. Custom report builder stores report definitions in JSON; does not allow raw SQL input.
5. Scheduled reports run via `php artisan schedule:run` with `ScheduledReportJob` dispatched to queue.
6. AR-021 API routes are under `/api/v1/crm/analytics` with API token authentication (institution-level token, not user token).

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for dashboards and reports.
3. Group V test cases document (`test-cases/sprint4_group_V_test_cases.md`).
4. Master tracker status and remarks update.

## Acceptance Gates

1. Institution dashboard loads in < 3s with correct totals by programme, campus, source, and period.
2. Counsellor dashboard shows own-only data; manager sees team; director sees full institution.
3. Funnel chart displays correct stage-wise conversion percentages.
4. All 9 standard reports return correct data and export to both Excel and PDF.
5. Custom report builder saves definition and renders output with selected fields and filters.
6. Scheduled report delivers correct Excel attachment to configured email recipients.
7. Date range filter and drill-down work across all dashboard widgets.
8. No cross-institution data visible on any dashboard or report.

## Risks and Mitigation

1. Query performance on large lead/application datasets:
   Mitigation: Create DB views or materialised summary tables; add indexes on `institution_id`, `created_at`, `status`, `source`.
2. Custom report builder SQL injection risk:
   Mitigation: Use query builder API with whitelisted field/filter keys; never concatenate raw user input into queries.

## Exit Criteria

1. ✅ AR-001 to AR-020 marked completed; AR-021 (Could Have) deferred to Sprint 5.
2. ✅ ~35 Pest tests passing (unit + feature).
3. ✅ User manual and test cases document published.
4. ✅ QA sign-off recorded.

---

## File Manifest

### Migrations
- `create_dashboard_metric_snapshots_table.php` — institution_id, campus_id, period_date, metric_key, metric_value, segmentation_json (daily aggregation for AR-001/AR-006 performance)
- `create_custom_reports_table.php` — institution_id, created_by, name, fields_json, filters_json, grouping_json, aggregations_json, is_scheduled, schedule_cron, recipients_json
- `create_report_schedules_table.php` — custom_report_id, cron_expression, last_run_at, next_run_at, recipients_json, format (excel/pdf)

### Models
- `App\Models\CRM\Analytics\DashboardMetricSnapshot`
- `App\Models\CRM\Analytics\CustomReport`
- `App\Models\CRM\Analytics\ReportSchedule`

### Services
- `App\Services\CRM\Analytics\DashboardScopeService` — resolve allowed institution/campus/counsellor scope from authenticated user role
- `App\Services\CRM\Analytics\InstitutionDashboardService` — AR-001: aggregate leads, applications, offers, enrolments, revenue by programme/campus/source/period
- `App\Services\CRM\Analytics\CounsellorDashboardService` — AR-002: leads owned, tasks completed, conversion rate, response time per counsellor
- `App\Services\CRM\Analytics\MarketingDashboardService` — AR-003: spend vs leads, CPL, CPE, channel ROI
- `App\Services\CRM\Analytics\FunnelAnalyticsService` — AR-004: stage-wise lead counts and conversion percentages
- `App\Services\CRM\Analytics\SeatAvailabilityService` — AR-005: programme seat count vs confirmed enrolments (live query)
- `App\Services\CRM\Analytics\ExecutiveDashboardService` — AR-006: KPI tiles and trend time series
- `App\Services\CRM\Analytics\ReportService` — standard reports (AR-009 to AR-017): parameterised query builders per report type
- `App\Services\CRM\Analytics\ReportExportService` — render report data to Excel (Laravel Excel) and PDF (DomPDF)
- `App\Services\CRM\Analytics\CustomReportBuilderService` — AR-018: interpret report definition JSON, build and execute safe query
- `App\Services\CRM\Analytics\AnalyticsApiService` — AR-021: paginated API data with field selection

### Jobs
- `App\Jobs\CRM\Analytics\ScheduledReportJob` — AR-020: load schedule, run report, export, email to recipients
- `App\Jobs\CRM\Analytics\RefreshDashboardSnapshotJob` — daily aggregation job to populate `dashboard_metric_snapshots`

### Controllers (Web)
- `App\Http\Controllers\CRM\Analytics\DashboardController` — institutionDashboard, counsellorDashboard, marketingDashboard, executiveDashboard, seatAvailability
- `App\Http\Controllers\CRM\Analytics\ReportController` — enquiryRegister, counsellorActivity, applicationStatus, sourceEffectiveness, lostLeadAnalysis, feeCollection, documentCompliance, yearOnYear, agentPerformance
- `App\Http\Controllers\CRM\Analytics\ReportExportController` — exportExcel, exportPdf (per report)
- `App\Http\Controllers\CRM\Analytics\CustomReportController` — index, create, store, show, edit, update, destroy, run
- `App\Http\Controllers\CRM\Analytics\ReportScheduleController` — index, create, store, edit, update, destroy

### Controllers (API)
- `App\Http\Controllers\Api\V1\CRM\Analytics\AnalyticsApiController` — leadsData, applicationsData, enrolmentsData, funnelData (AR-021)

### Livewire Components
- `App\Livewire\CRM\Analytics\DateRangeFilter` — shared date picker with preset ranges (Today, This Week, This Month, Custom)
- `App\Livewire\CRM\Analytics\FunnelChart` — renders funnel using ApexCharts
- `App\Livewire\CRM\Analytics\KpiTileRow` — executive dashboard KPI tiles with trend indicators

### Views (Blade)
- `resources/views/crm/analytics/dashboards/institution.blade.php`
- `resources/views/crm/analytics/dashboards/counsellor.blade.php`
- `resources/views/crm/analytics/dashboards/marketing.blade.php`
- `resources/views/crm/analytics/dashboards/executive.blade.php`
- `resources/views/crm/analytics/dashboards/seat-availability.blade.php`
- `resources/views/crm/analytics/reports/enquiry-register.blade.php`
- `resources/views/crm/analytics/reports/counsellor-activity.blade.php`
- `resources/views/crm/analytics/reports/application-status.blade.php`
- `resources/views/crm/analytics/reports/source-effectiveness.blade.php`
- `resources/views/crm/analytics/reports/lost-lead-analysis.blade.php`
- `resources/views/crm/analytics/reports/fee-collection.blade.php`
- `resources/views/crm/analytics/reports/document-compliance.blade.php`
- `resources/views/crm/analytics/reports/year-on-year.blade.php`
- `resources/views/crm/analytics/reports/agent-performance.blade.php`
- `resources/views/crm/analytics/custom-reports/index.blade.php`
- `resources/views/crm/analytics/custom-reports/create.blade.php`
- `resources/views/crm/analytics/custom-reports/result.blade.php`
- `resources/views/crm/analytics/report-schedules/index.blade.php`
- `resources/views/crm/analytics/report-schedules/create.blade.php`

### Policies
- `App\Policies\CRM\Analytics\DashboardPolicy`
- `App\Policies\CRM\Analytics\ReportPolicy`
- `App\Policies\CRM\Analytics\CustomReportPolicy`

### Seeders
- `Database\Seeders\CRM\Analytics\AnalyticsRolePermissionSeeder`

### Tests
- `tests/Unit/CRM/Analytics/DashboardScopeServiceTest.php`
- `tests/Unit/CRM/Analytics/FunnelAnalyticsServiceTest.php`
- `tests/Unit/CRM/Analytics/ReportExportServiceTest.php`
- `tests/Unit/CRM/Analytics/CustomReportBuilderServiceTest.php`
- `tests/Feature/CRM/Analytics/InstitutionDashboardTest.php`
- `tests/Feature/CRM/Analytics/CounsellorDashboardScopeTest.php`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php`
- `tests/Feature/CRM/Analytics/ReportExcelExportTest.php`
- `tests/Feature/CRM/Analytics/ReportPdfExportTest.php`
- `tests/Feature/CRM/Analytics/CustomReportBuilderTest.php`
- `tests/Feature/CRM/Analytics/ScheduledReportJobTest.php`
- `tests/Feature/CRM/Analytics/AnalyticsApiTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| AR-001 | Institution admissions dashboard | `InstitutionDashboardService`, `DashboardController::institutionDashboard()` |
| AR-002 | Counsellor performance dashboard | `CounsellorDashboardService`, `DashboardController::counsellorDashboard()` |
| AR-003 | Marketing campaign dashboard | `MarketingDashboardService`, `DashboardController::marketingDashboard()` |
| AR-004 | Admissions funnel visualisation | `FunnelAnalyticsService`, `FunnelChart` Livewire |
| AR-005 | Seat availability vs enrolments | `SeatAvailabilityService`, `DashboardController::seatAvailability()` |
| AR-006 | Executive dashboard | `ExecutiveDashboardService`, `DashboardController::executiveDashboard()` |
| AR-007 | Role-based data scoping | `DashboardScopeService` applied in all dashboard services |
| AR-008 | Date range filter + drill-down | `DateRangeFilter` Livewire, all dashboard/report controllers |
| AR-009 | Enquiry Register Report | `ReportController::enquiryRegister()` |
| AR-010 | Counsellor Activity Report | `ReportController::counsellorActivity()` |
| AR-011 | Application Status Report | `ReportController::applicationStatus()` |
| AR-012 | Source Effectiveness Report | `ReportController::sourceEffectiveness()` |
| AR-013 | Lost Lead Analysis Report | `ReportController::lostLeadAnalysis()` |
| AR-014 | Fee Collection Report | `ReportController::feeCollection()` |
| AR-015 | Document Compliance Report | `ReportController::documentCompliance()` |
| AR-016 | Year-on-Year Comparison Report | `ReportController::yearOnYear()` |
| AR-017 | Agent Performance Report | `ReportController::agentPerformance()` |
| AR-018 | Custom report builder | `CustomReportService`, `CustomReportWebController`, `CustomReportController` (API) |
| AR-019 | Export to Excel and PDF | `ReportExportService`, `ReportExportController`, `StandardReportExport`, `GET reports/{report}/export?format=excel|pdf` |
| AR-020 | Scheduled report delivery | `ReportSchedulerService`, `ReportDeliveryJob`, `ReportSchedulerWebController`, `ReportSchedulerController` (API) |
| AR-017 | Agent Performance Report | `ReportService::agentPerformance()`, `ReportController::agentPerformance()`, `ReportExportController` |
| AR-019 | Export to Excel and PDF | `ReportExportService`, `ReportExportController`, `StandardReportExport`, `GET reports/{report}/export?format=excel\|pdf` |
| AR-021 | API analytics access (Power BI / Tableau) | ⏳ Deferred to Sprint 5 (Could Have) — `AnalyticsApiController` under `/api/v1/crm/analytics` not yet implemented |

---

## Security Checklist

- [x] All dashboard routes protected by `auth` and role-specific `permission` middleware.
- [x] `DashboardScopeService` resolves scope at service layer; policy double-checks in controllers.
- [x] Custom report builder field whitelist validated server-side; no raw SQL accepted.
- [ ] AR-021 API uses institution-level API token (separate from user session tokens). *(deferred — AR-021 Could Have)*
- [x] Scheduled report job validates recipient email list ownership before sending.
- [x] Excel and PDF exports do not expose cross-institution data (scope enforced in query before export via `ReportExportService`).

---

## Implementation Log

### AR-009 — Enquiry Register Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/ReportService.php` — `enquiryRegister()`: scoped, filtered paginator (20/page); supports date range, source, status, campus, counsellor filters; respects DashboardScopeService restrictions
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — `enquiryRegister()` gated by `crm.reports.view`; passes `$sources`, `$statuses`, `$campuses`, `$counsellors` to view
- `app/Policies/CRM/Analytics/ReportPolicy.php` — `viewAny`, `manage`, `export` methods delegating to Spatie permissions
- `resources/views/crm/analytics/reports/enquiry-register.blade.php` — filter form (date range, source, status, campus, counsellor), paginated table with row number, name, contact (mobile+email), source pill, campus, programme, status badge, counsellor, enquiry date+time; export placeholder buttons (AR-019)

**Files Modified:**
- `routes/web.php` — added `reports` prefix group inside `analytics` with `crm.analytics.reports.enquiry-register` route
- `resources/views/components/layouts/crm.blade.php` — added Enquiry Register sidebar link under `@can('crm.reports.view')`
- `app/Enums/CRM/LeadStatus.php` — added `badgeClass()` method (returns full Tailwind class string); fixes missing method used by drill-down view (AR-008) and this report

**Notes:** Counsellor sees own leads only; manager sees campus; director sees institution-wide. Campus and counsellor filter dropdowns only rendered when the scope permits (not shown when already restricted by scope). Export buttons are placeholders pending AR-019. `badgeClass()` added to `LeadStatus` enum — fixes the same missing-method issue in the existing AR-008 drill-down view.

---

### AR-012 — Source Effectiveness Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `resources/views/crm/analytics/reports/source-effectiveness.blade.php` — filter form (date range, campus); grouped summary table with source label, leads, applied, offered, enrolled, Lead→Apply %, Apply→Enrol %, Overall %; ROI Signal badge (High ≥20%, Medium ≥10%, Low >0%, No enrolments); totals footer row; export placeholder buttons (AR-019)

**Files Modified:**
- `app/Services/CRM/Analytics/ReportService.php` — added `sourceEffectiveness()`: `DB::table('leads')` with `selectRaw` CASE/SUM aggregations grouped by source; hardcoded status IN strings (internal constants, not user input); scope-aware (campus_id, counsellor_ids); campus filter override for manager/director
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `sourceEffectiveness()` method; passes `$rows`, `$filters`, `$campuses`, `$scope` to view (no programme/counsellor dropdowns — source effectiveness is a channel-level view)
- `routes/web.php` — added `crm.analytics.reports.source-effectiveness` route
- `resources/views/components/layouts/crm.blade.php` — added Source Effectiveness sidebar link under `@can('crm.reports.view')`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — added 6 tests: access, redirect, 403, date filter passthrough, per-source grouping + period boundary, enrolled count accuracy, counsellor scope isolation

**Notes:** Report uses `DB::table` (not Eloquent) since it's a pure aggregation with no model hydration needed — returns a `Collection<stdClass>`. Source label resolved in Blade via `LeadSource::cases()` keyed by value with graceful fallback for any unlabelled values. ROI Signal thresholds match the Marketing Dashboard (AR-003) channel ROI colour logic for visual consistency.

---

### AR-011 — Application Status Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `resources/views/crm/analytics/reports/application-status.blade.php` — filter form (date, status, programme, campus, counsellor); paginated table with #, applicant name+contact, programme, campus, status badge, counsellor, submitted date, stage-since (diffForHumans); View link gated by `crm.applications.view`; export placeholder buttons (AR-019)

**Files Modified:**
- `app/Enums/CRM/ApplicationStatus.php` — added `badgeClass()` method returning full Tailwind class strings (mirrors `LeadStatus::badgeClass()`)
- `app/Services/CRM/Analytics/ReportService.php` — added `applicationStatus()`: scoped, filtered paginator (20/page); filters: date range (submitted_at), status, programme_id, campus_id, counsellor_id; eager-loads lead, programme, assignedCounsellor, campus
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `applicationStatus()` method; passes `$applications`, `$filters`, `$campuses`, `$programmes`, `$counsellors`, `$statuses`, `$scope` to view
- `routes/web.php` — added `crm.analytics.reports.application-status` route
- `resources/views/components/layouts/crm.blade.php` — added Application Status sidebar link under `@can('crm.reports.view')`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — added 6 tests for AR-011 (access, redirect, 403, date filter, period inclusion, counsellor scope isolation, status filter narrowing)

**Notes:** `submitted_at` is the date anchor for the period filter (consistent with how the application creation flow sets it). `stage_entered_at` shown as `diffForHumans()` to give reviewers an at-a-glance age for each pipeline stage. No new permissions seeded — `crm.reports.view` covers all roles.

---

### AR-010 — Counsellor Activity Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/ReportService.php` — added `counsellorActivity()`: scoped `User` query with 6 correlated sub-selects (`new_leads`, `converted_leads`, `tasks_completed`, `tasks_overdue`, `calls_made`, `sessions_completed`); ordered by `new_leads DESC`; supports date range, campus, counsellor filters; respects DashboardScopeService scope (counsellor sees own row only, manager sees campus, director sees all)
- `resources/views/crm/analytics/reports/counsellor-activity.blade.php` — filter form (date range, campus, counsellor); results table with #, counsellor, campus, new leads, converted, conversion rate (colour-coded), tasks completed, overdue badge, calls made, sessions; performance badge (High ≥30%, Medium ≥15%, Low <15%); totals footer row; export placeholder buttons (AR-019)
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — 8 feature tests covering access by role (director/manager/counsellor), 403 for no-permission user, redirect for unauthenticated, scope isolation (counsellor sees own row only), date filter passthrough, new_leads count accuracy, tasks_completed count accuracy

**Files Modified:**
- `app/Services/CRM/Analytics/ReportService.php` — `counsellorActivity()` method added (file already exists from AR-009)
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `counsellorActivity()` method gated by `crm.reports.view`; passes `$rows`, `$filters`, `$campuses`, `$counsellors`, `$scope` to view
- `routes/web.php` — added `crm.analytics.reports.counsellor-activity` route under reports prefix group
- `resources/views/components/layouts/crm.blade.php` — added Counsellor Activity sidebar link under `@can('crm.reports.view')`, between Enquiry Register and Custom Reports

**Notes:** Conversion rate computed in Blade from `new_leads` and `converted_leads` sub-select counts (fee_paid or enrolled, created in period). `tasks_overdue` is a point-in-time snapshot (all currently overdue tasks assigned to counsellor, not period-scoped) — this mirrors the AR-002 dashboard behaviour. No new permissions required — `crm.reports.view` already covers all roles via AR-009 seeder.

---

### AR-007 — Role-Based Dashboard Scope
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `database/migrations/2026_04_30_100000_create_dashboard_metric_snapshots_table.php`
- `app/Models/CRM/Analytics/DashboardMetricSnapshot.php`
- `app/Services/CRM/Analytics/DashboardScopeService.php`
- `app/Policies/CRM/Analytics/DashboardPolicy.php`
- `app/Providers/CRM/CrmAnalyticsServiceProvider.php`
- `database/seeders/CRM/Analytics/AnalyticsRolePermissionSeeder.php`
- `app/Jobs/CRM/Analytics/RefreshDashboardSnapshotJob.php`
- `app/Console/Commands/CRM/Analytics/RefreshDashboardSnapshotsCommand.php`
- `tests/Unit/CRM/Analytics/DashboardScopeServiceTest.php`

**Files Modified:**
- `bootstrap/providers.php` — registered CrmAnalyticsServiceProvider
- `database/seeders/DatabaseSeeder.php` — added AnalyticsRolePermissionSeeder
- `routes/console.php` — wired nightly RefreshDashboardSnapshotsCommand

**Notes:** 6/6 unit tests passing. Migration applied. Counsellor/manager/director scope resolution verified.

### AR-001 — Institution Admissions Dashboard
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/InstitutionDashboardService.php` — getSummaryKpis, getByProgramme, getBySource, getMonthlyTrend (snapshot + live fallback)
- `app/Http/Controllers/CRM/Analytics/DashboardController.php` — institutionDashboard() gated by crm.analytics.institution
- `resources/views/crm/analytics/dashboards/institution.blade.php` — KPI tiles, Chart.js bar+doughnut, programme table, date filter
- `tests/Feature/CRM/Analytics/InstitutionDashboardTest.php` — 4 feature tests

**Files Modified:**
- `routes/web.php` — added crm.analytics.dashboards.institution route under analytics prefix group
- `resources/views/components/layouts/crm.blade.php` — added Institution Dashboard sidebar link under @can('crm.analytics.institution')
- `app/Providers/CRM/CrmAnalyticsServiceProvider.php` — removed infinite-recursion Gate::define calls (bug fix; Spatie permissions handle can() natively)

**Notes:** Feature tests pass (4/4). Infinite recursion bug in service provider fixed — Gate::define with same name as Spatie permission caused app-wide hang. App confirmed loading correctly post-fix.

### AR-002 — Counsellor Performance Dashboard
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/CounsellorDashboardService.php` — getPerformanceGrid (team view), getOwnKpis (self view); queries leads + tasks tables scoped by institution/counsellor
- `resources/views/crm/analytics/dashboards/counsellor.blade.php` — own KPI tiles, avg response card, team ranking table with performance badge (High/Medium/Low)

**Files Modified:**
- `app/Http/Controllers/CRM/Analytics/DashboardController.php` — added counsellorDashboard() method + CounsellorDashboardService injection
- `routes/web.php` — added crm.analytics.dashboards.counsellor route (middleware: crm.analytics.view)
- `resources/views/components/layouts/crm.blade.php` — added Counsellor Dashboard sidebar link under @can('crm.analytics.view')

**Notes:** Counsellors see own KPIs only; managers/directors see full team ranking table. Performance badge thresholds: ≥30% = High, ≥15% = Medium, <15% = Low. Route verified via artisan route:list.

### AR-003 — Marketing Campaign Dashboard
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/MarketingDashboardService.php` — getSummaryKpis (total spend, leads, enrolments, revenue, CPL, CPE), getByChannel (per-source breakdown with ROI), getMonthlyTrend (12-month spend vs leads series for Chart.js)
- `resources/views/crm/analytics/dashboards/marketing.blade.php` — 6 KPI tiles, dual-axis Chart.js bar+line trend, channel breakdown table with ROI badge (green ≥100%, amber ≥0%, red <0%)

**Files Modified:**
- `app/Http/Controllers/CRM/Analytics/DashboardController.php` — added marketingDashboard() + MarketingDashboardService injection; gated by crm.analytics.marketing
- `routes/web.php` — added crm.analytics.dashboards.marketing route under analytics dashboards prefix
- `resources/views/components/layouts/crm.blade.php` — added Marketing Dashboard sidebar link under @can('crm.analytics.marketing')
- `database/seeders/CRM/Analytics/AnalyticsRolePermissionSeeder.php` — registered crm.analytics.marketing permission; granted to admissions_manager, admissions_director, institution-admin, super-admin

**Notes:** Revenue attributed per channel via `payment_transactions.lead_uuid → leads.uuid` join. ROI = ((revenue − spend) / spend) × 100; shown as "No spend" when no campaign spend exists for that channel. Spend matched to period by campaign_spends.period_start/period_end range. CPL and CPE display "—" when denominator is zero.

### AR-008 — Date Range Filter and Drill-down to Individual Lead Records
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Http/Controllers/CRM/Analytics/DrillDownController.php` — `leads()` method: resolves scope via `DashboardScopeService`; whitelists `metric` (leads/applications/offers/enrolments), validates `source` against `LeadSource` enum, validates `programme_id` belongs to scoped institution; builds paginated Eloquent query with `withoutGlobalScopes()`, `whereNull('deleted_at')`, status group filter, and optional source/programme constraints; eager-loads `assignedCounsellor` + primary `programmeInterests`
- `resources/views/crm/analytics/drill-down/leads.blade.php` — context header (metric label, date range, optional source/programme name), total record count, paginated table (Name→show link, status badge, source pill, primary programme, counsellor, created date), `history.back()` back button; `View →` link gated by `crm.leads.view`

**Files Modified:**
- `routes/web.php` — added `crm.analytics.drill-down.leads` route under `analytics` prefix group
- `app/Services/CRM/Analytics/InstitutionDashboardService.php` — added `p.id as programme_id` to `getByProgramme()` select, enabling per-programme drill-down links
- `resources/views/crm/analytics/dashboards/institution.blade.php` — KPI tiles (leads/applications/offers/enrolments) wrapped as drill-down links; programme table rows (applications/offers/enrolments columns) wrapped as drill-down links with `programme_id`
- `resources/views/crm/analytics/dashboards/executive.blade.php` — KPI tiles (leads/applications/offers/enrolments) wrapped as drill-down links; rate/revenue tiles remain non-linkable
- `resources/views/crm/analytics/dashboards/counsellor.blade.php` — "My Leads" and "Converted" KPI tiles wrapped as drill-down links

**Notes:** Date range filter already existed on all dashboards from prior AR implementations (AR-001 to AR-004, AR-006). This item formalises drill-down as the complementary feature. Security: scope resolution is always server-side; `metric`, `source`, and `programme_id` params are all whitelisted/validated before use. `crm.analytics.view` gate applied.

---

### AR-006 — Director/Management Executive Dashboard
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/ExecutiveDashboardService.php` — `getKpiTiles()` (6 KPIs with period-over-period delta via single-query CASE/SUM; prior period = same duration before selected range), `getMonthlyTrend()` (12-month series from `dashboard_metric_snapshots` for leads/enrolments/revenue), `getTopProgrammes()` (top 5 by enrolment via `lead_programme_interests`), `getCampusBreakdown()` (per-campus leads/applications/enrolments via LEFT JOIN on `campuses`)
- `resources/views/crm/analytics/dashboards/executive.blade.php` — 6 KPI tiles with ↑/↓ trend arrows (green/red, pp for rate), Chart.js dual-axis 12-month line chart (leads+enrolments left Y, revenue right Y), top-5 programmes table with conversion badge, campus breakdown table

**Files Modified:**
- `app/Http/Controllers/CRM/Analytics/DashboardController.php` — added `executiveDashboard()` + `ExecutiveDashboardService` injection; gated by `crm.analytics.executive`
- `routes/web.php` — added `crm.analytics.dashboards.executive` route under `can:crm.analytics.executive`
- `resources/views/components/layouts/crm.blade.php` — added Executive Dashboard sidebar link under `@can('crm.analytics.executive')`

**Notes:** Enrolment rate trend delta expressed in percentage points (pp), not relative %. Revenue uses existing `payment_transactions` join pattern. `crm.analytics.executive` already seeded for `admissions_director`, `institution-admin`, `super-admin`. No new permissions required.

---

### AR-005 — Seat Availability vs Confirmed Enrolments
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/SeatAvailabilityService.php` — `getProgrammeSeatData()` (live LEFT JOIN of crm_programmes × enrolled lead_programme_interests, returns per-programme capacity/enrolment/utilisation/status), `getSummaryKpis()` (institution-wide aggregates)
- `resources/views/crm/analytics/dashboards/seat-availability.blade.php` — 6 KPI tiles, progress-bar utilisation per programme, colour-coded Full/Critical/Healthy/Uncapped status badges

**Files Modified:**
- `app/Http/Controllers/CRM/Analytics/DashboardController.php` — added `seatAvailability()` method + `SeatAvailabilityService` injection; gated by `crm.analytics.view`
- `routes/web.php` — added `crm.analytics.dashboards.seat-availability` route
- `resources/views/components/layouts/crm.blade.php` — added Seat Availability sidebar link under `@can('crm.analytics.view')`

**Notes:** No date filter — this is a real-time snapshot. Campus scoping applied via DashboardScopeService. Enrolments counted from `lead_programme_interests.status = 'enrolled'` joined to `leads` table for institution/campus isolation. Status thresholds: Full ≥100%, Critical ≥80%, Healthy <80%, Uncapped when intake_capacity = 0. No changes to permissions seeder — `crm.analytics.view` already covers all roles.

---

### AR-013 — Lost Lead Analysis Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `resources/views/crm/analytics/reports/lost-lead-analysis.blade.php` — filter form (date range, lost reason, source, campus, counsellor); reason summary cards (clickable filter toggle with %, mini progress bar, colour-coded by severity); paginated detail table with #, name+programme, source pill, campus, counsellor, lost reason badge, enquiry date, lost date, days-to-loss (colour-coded ≤7d red, ≤30d amber); View link gated by `crm.leads.view`; export placeholder buttons (AR-019)

**Files Modified:**
- `app/Services/CRM/Analytics/ReportService.php` — added `lostLeadAnalysis()`: scoped + filtered paginator (status='lost', date anchor=status_changed_at, filters: source, lost_reason, campus_id, counsellor_id); added `lostLeadsByReason()`: DB aggregate grouped by lost_reason (respects all filters except lost_reason, so summary always shows full breakdown)
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `lostLeadAnalysis()` gated by `crm.reports.view`; imports `LostReason` enum; passes `$leads`, `$reasonSummary`, `$filters`, `$campuses`, `$counsellors`, `$sources`, `$lostReasons`, `$scope` to view
- `routes/web.php` — added `crm.analytics.reports.lost-lead-analysis` route under reports prefix group
- `resources/views/components/layouts/crm.blade.php` — added Lost Lead Analysis sidebar link after Source Effectiveness under `@can('crm.reports.view')`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — added 8 tests: access, redirect, 403, date filter passthrough, period inclusion + non-lost exclusion, counsellor scope isolation, lost_reason filter narrowing, reason summary count accuracy

**Notes:** Date anchor is `status_changed_at`, not `created_at` — this is intentional: the report answers "what leads were lost this period", not "what leads created this period are now lost". Reason summary cards are clickable toggles that add/remove the `lost_reason` filter without a form submission. Days-to-loss threshold colours: ≤7d = red (quick drop-off), ≤30d = amber, >30d = grey. 8/8 tests passing.

---

### AR-014 — Fee Collection Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `resources/views/crm/analytics/reports/fee-collection.blade.php` — 4 summary tiles (Collected ₹, Pending ₹, Refunded ₹, Total Transactions); filter form (date range, status, fee_type, programme, campus, counsellor); paginated table with student name+mobile, programme, fee type badge, amount, gateway, status badge (green=success, amber=pending/initiated, purple=refund, red=failed/cancelled), attempted date+time, confirmed date, counsellor; export placeholder buttons (AR-019)

**Files Modified:**
- `app/Services/CRM/Analytics/ReportService.php` — added `feeCollection()`: scoped+filtered paginator via `PaymentTransaction::withoutGlobalScopes()`; counsellor scope via `whereHas('lead', withoutGlobalScopes()->whereIn(...))` ; filters: date range on `attempted_at`, status, fee_type, programme_id (via `whereHas('application')`), campus_id, counsellor_id; eager-loads lead+assignedCounsellor, application+programme. Added `feeCollectionSummary()`: `DB::table` aggregate with `COALESCE/CASE WHEN` for collected/pending/refunded/total; counsellor scope via `whereExists` subquery on `leads` table to avoid JOIN row duplication
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `feeCollection()` method gated by `crm.reports.view`; imports `FeeType` + `PaymentStatus` enums; passes `$transactions`, `$summary`, `$filters`, `$campuses`, `$programmes`, `$counsellors`, `$feeTypes`, `$statuses`, `$scope`
- `routes/web.php` — added `crm.analytics.reports.fee-collection` route under reports prefix group
- `resources/views/components/layouts/crm.blade.php` — added Fee Collection sidebar link (₹ currency icon) after Lost Lead Analysis under `@can('crm.reports.view')`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — added 9 tests: access (director), redirect (unauthenticated), 403 (no permission), date filter passthrough, period inclusion, period exclusion, counsellor scope isolation, status filter narrowing, summary collected+pending amounts

**Notes:** Date anchor is `attempted_at` (always populated) — `confirmed_at` is shown in the table but only set for successful transactions. Counsellor scope resolved indirectly via lead's `assigned_counsellor_id` (PaymentTransaction has no direct counsellor FK). Summary uses `whereExists` on `leads` table rather than a JOIN to avoid row fan-out when a lead has multiple transactions. Tests use `Campus::create(...)` directly (no CampusFactory exists in this project). 9/9 tests passing.

---

### AR-016 — Year-on-Year Comparison Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `resources/views/crm/analytics/reports/year-on-year.blade.php` — 4 KPI tiles (Leads, Applications, Enrolments, Revenue) each showing current year, previous year, Δ, and ↑/↓% trend arrow; filter form (year picker 5y range, group_by selector, campus override); breakdown table with prev/current/delta columns per metric for each programme/source/campus row; totals footer row; AR-019 export placeholder buttons

**Files Modified:**
- `app/Services/CRM/Analytics/ReportService.php` — added `yearOnYearSummary()`: single `DB::table('leads')` CASE/SUM query for leads/applied/enrolled across both years + separate `payment_transactions` revenue aggregate; returns typed stdClass. Added `yearOnYearBreakdown()`: `match($groupBy)` branches for programme (LEFT JOIN `lead_programme_interests` + `crm_programmes`), source (GROUP BY `l.source`), campus (JOIN `campuses`); uses `(clone $base)` to avoid query builder state mutation
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `yearOnYear()` gated by `crm.reports.view`; 5-year `$years` range; passes `$summary`, `$breakdown`, `$filters`, `$campuses`, `$years`, `$scope`
- `routes/web.php` — added `crm.analytics.reports.year-on-year` route
- `resources/views/components/layouts/crm.blade.php` — added Year-on-Year sidebar link after Document Compliance under `@can('crm.reports.view')`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — added 7 tests: director access, redirect, 403, year filter + summary years, current_leads accuracy, enrolled count, group_by=source breakdown, counsellor scope isolation, delta/pct calculation

**Notes:** `year`/`prevYear` are cast `int` before `selectRaw` interpolation — no SQL injection risk. Revenue appears in summary tiles only (not breakdown table) to avoid payment_transactions JOIN fan-out. LEFT JOIN on `lead_programme_interests` ensures leads without a primary programme appear as `(No Programme)` rather than being silently dropped.

---

### AR-015 — Document Compliance Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `resources/views/crm/analytics/reports/document-compliance.blade.php` — 6 summary tiles (Applications, Total Docs, Verified, Pending Review, Rejected, Missing); filter form (date range, compliance status, programme, campus, counsellor); paginated table with #, applicant name+mobile, programme, campus, counsellor, per-doc-status counts (Verified/Pending/Rejected/Missing), compliance badge (Compliant/Has Rejections/No Docs/%), submitted date, View link gated by `crm.applications.view`; AR-019 export placeholder buttons

**Files Modified:**
- `app/Services/CRM/Analytics/ReportService.php` — added `documentCompliance()`: `Application::withoutGlobalScopes()` with 5 `selectSub` sub-selects (total_docs, verified_docs, pending_docs, rejected_docs, missing_docs) from `application_documents`; scope-aware (campus/counsellor); filters: date range on `submitted_at`, programme_id, campus_id, counsellor_id, compliance (compliant|pending|rejected using `whereHas`/`whereDoesntHave`). Added `documentComplianceSummary()`: plucks scoped application UUIDs then runs a single `DB::table` CASE/SUM aggregate for doc counts; returns typed stdClass with total_applications, total_docs, verified_docs, pending_docs, rejected_docs, missing_docs
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `documentCompliance()` gated by `crm.reports.view`; passes `$applications`, `$summary`, `$filters`, `$campuses`, `$programmes`, `$counsellors`, `$scope` to view
- `routes/web.php` — added `crm.analytics.reports.document-compliance` route under reports prefix group
- `resources/views/components/layouts/crm.blade.php` — added Document Compliance sidebar link (checklist icon) after Fee Collection under `@can('crm.reports.view')`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — added 7 tests: director access, redirect unauthenticated, 403 no-permission, date filter passthrough, applications-with-docs appear with correct counts, outside-period exclusion, counsellor scope isolation, compliance=rejected filter, summary tile doc counts

**Notes:** Compliance badge logic: Compliant (pct=100% and no non-verified), Has Rejections (any rejected > 0), No Docs (total=0), partial % otherwise. `documentComplianceSummary()` uses pluck+whereIn to avoid a complex JOIN fan-out across the `application_documents` aggregate. No new permissions required — `crm.reports.view` already seeded for all roles.

---

### AR-004 — Admissions Funnel Visualisation
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/FunnelAnalyticsService.php` — `getFunnelStages()` (single-query cumulative stage counts via CASE/SUM; computes conversion_rate and drop_off per stage), `getFunnelBySource()` (per-channel enquiry/applied/enrolled breakdown)
- `resources/views/crm/analytics/dashboards/funnel.blade.php` — ApexCharts horizontal isFunnel bar chart with custom tooltip (count + conversion + drop-off); stage breakdown table with colour-coded conversion badges; per-source summary table with lead→enrol %

**Files Modified:**
- `app/Http/Controllers/CRM/Analytics/DashboardController.php` — added `funnelDashboard()` + `FunnelAnalyticsService` injection; gated by `crm.analytics.view`
- `routes/web.php` — added `crm.analytics.dashboards.funnel` route
- `resources/views/components/layouts/crm.blade.php` — added Admissions Funnel sidebar link under `@can('crm.analytics.view')`

**Notes:** Funnel is cumulative — each stage counts leads that reached that stage or progressed beyond it, matching the standard admissions funnel shape. 7 stages mapped from LeadStatus enum: Enquiry → Contacted → Counselled → Applied → Offer Issued → Fee Paid → Enrolled. Campus and counsellor scoping applied via DashboardScopeService. ApexCharts loaded from CDN (`apexcharts@3`).

---

### AR-018 — Custom Report Builder
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/CustomReportService.php` — `paginate()`, `create()`, `update()`, `delete()`, `run()` (builds safe `DB::table` query from report entity + selected_fields + filters JSON; returns `{headers, rows, total}`), `recordExport()` (DPDP audit trail for export actions). `buildQuery()` private method applies entity-scoped query with institution_id, whereNull deleted_at, whitelisted field selection, filter array iteration, and optional GROUP BY.
- `app/Http/Controllers/Web/CRM/CustomReportWebController.php` — full CRUD (index, create, store, show, edit, update, destroy) + `run()` action; gated by `crm.reports.view` (read) and `crm.reports.manage` (write)
- `app/Http/Controllers/Api/CRM/CustomReportController.php` — API CRUD for custom report definitions
- `resources/views/crm/analytics/custom-reports/index.blade.php` — report list with last-run date, run button, manage links
- `resources/views/crm/analytics/custom-reports/create.blade.php` — field selector, filter builder, grouping options
- `resources/views/crm/analytics/custom-reports/show.blade.php` — report result table with export placeholder buttons

**Files Modified:**
- `routes/web.php` — full CRUD + run routes under `reports/custom` prefix with `crm.reports.view` / `crm.reports.manage` middleware
- `resources/views/components/layouts/crm.blade.php` — Custom Reports sidebar link under `@can('crm.reports.view')`

**Notes:** Report definitions stored as JSON (entity, selected_fields, filters, group_by, sort_field, sort_direction). `buildQuery()` uses `DB::table` with whitelisted field names from the report definition; no raw user SQL accepted. `recordExport()` writes to `report_exports` table for DPDP audit compliance. Actual file generation for exports is dispatched as a background job (not inside this service). The service class is `CustomReportService` (not `CustomReportBuilderService` as named in the original design doc).

---

### AR-020 — Scheduled Report Delivery
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Services/CRM/Analytics/ReportSchedulerService.php` — `paginate()`, `create()` (sets `next_run_at` on creation), `update()` (recalculates `next_run_at`), `delete()`, `dispatchDelivery()` (creates `ReportDelivery` record and dispatches `ReportDeliveryJob` to `crm-analytics` queue), `processDueSchedules()` (called from Laravel scheduler; iterates due schedules, dispatches delivery, updates `last_sent_at` and `next_run_at`). `calculateNextRun()` resolves next fire time from `ReportFrequency` enum (Daily / Weekly / Monthly) and configured `run_time`.
- `app/Jobs/CRM/Analytics/ReportDeliveryJob.php` — processes a single `ReportDelivery` record: runs the linked custom report, exports to the chosen format, emails the attachment to configured recipients, updates delivery status.
- `app/Http/Controllers/Web/CRM/ReportSchedulerWebController.php` — index, create, store, edit, update, destroy, dispatch (manual trigger); gated by `crm.reports.view` / `crm.reports.manage`
- `app/Http/Controllers/Api/CRM/ReportSchedulerController.php` — API equivalents for schedule management
- `resources/views/crm/analytics/report-scheduler/index.blade.php` — schedule list with next_run_at, last_sent_at, format, recipient count, dispatch button
- `resources/views/crm/analytics/report-scheduler/create.blade.php` — report picker, frequency selector (Daily/Weekly/Monthly), run_time, day_of_week/day_of_month conditional fields, recipient emails, format (excel/pdf)

**Files Modified:**
- `routes/web.php` — full CRUD + dispatch routes under `reports/scheduler` prefix with `crm.reports.view` / `crm.reports.manage` middleware
- `resources/views/components/layouts/crm.blade.php` — Report Scheduler sidebar link under `@can('crm.reports.view')`
- `routes/console.php` — `ReportSchedulerService::processDueSchedules()` wired to Laravel scheduler (runs every minute via `schedule:run`)

**Notes:** `ReportDeliveryJob` dispatched to `crm-analytics` queue — requires queue worker to be running. Manual dispatch via the UI (POST `/{reportSchedule:uuid}/dispatch`) is available for immediate delivery without waiting for the cron cycle. `next_run_at` is always recalculated relative to now() at create/update time, not relative to the previous run, so missed runs don't cascade. The service class is `ReportSchedulerService` + `ReportDeliveryJob` (the original design doc named these `ScheduledReportJob` — the implementation split delivery into a dedicated Job and a scheduler service).

---

### AR-017 — Agent Performance Report
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `resources/views/crm/analytics/reports/agent-performance.blade.php` — filter form (date range, agent, campus); ranked summary table with agent name, referral code, leads submitted, applications, enrolments, conversion rate badge (High ≥30%/Medium ≥15%/Low), revenue attributed (₹), commissions accrued (₹), net commission status; totals footer row; AR-019 export buttons wired

**Files Modified:**
- `app/Services/CRM/Analytics/ReportService.php` — added `agentPerformance()`: scoped `Agent` query with correlated sub-selects for `leads_submitted`, `applications`, `enrolments`, `revenue_attributed`, `commissions_accrued`; filters: date range on lead `created_at`, agent_id, campus_id; institution-level scope (no counsellor restriction — agents are institution-wide)
- `app/Http/Controllers/CRM/Analytics/ReportController.php` — added `agentPerformance()` gated by `crm.reports.view`; passes `$rows`, `$filters`, `$agents`, `$campuses`, `$scope` to view
- `routes/web.php` — added `crm.analytics.reports.agent-performance` route under reports prefix group
- `resources/views/components/layouts/crm.blade.php` — added Agent Performance sidebar link after Year-on-Year under `@can('crm.reports.view')`
- `tests/Feature/CRM/Analytics/StandardReportsTest.php` — added 7 tests: director access, redirect unauthenticated, 403 no-permission, date filter passthrough, leads_submitted count accuracy, enrolment conversion accuracy, commission accrual sum

**Notes:** Agent scope is institution-wide only — counsellor-level agents do not exist, so the counsellor restriction from `DashboardScopeService` is bypassed for this report. Revenue attributed via `payment_transactions.lead_uuid` joined through agent referral attribution (`leads.agent_id`). Commission accrual summed from `agent_commissions.amount` where `status = 'accrued'` or `'paid'`.

---

### AR-019 — Export to Excel and PDF
**Date:** 2026-04-23
**Status:** ✅ Completed
**Files Created:**
- `app/Exports/CRM/Analytics/StandardReportExport.php` — implements `FromCollection`, `WithHeadings`, `WithStyles`, `ShouldAutoSize`; accepts report type + data collection; column headings resolved per report type from a static `headingsMap()` method; currency columns formatted with `NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2`
- `app/Http/Controllers/CRM/Analytics/ReportExportController.php` — `export()` method: validates `report` (whitelisted enum of 9 report types) and `format` (excel|pdf); calls `ReportService` to fetch unscoped (non-paginated) data; dispatches to `ReportExportService`; returns streamed file download
- `app/Services/CRM/Analytics/ReportExportService.php` — `exportExcel()`: wraps `StandardReportExport` via `Excel::download()`; `exportPdf()`: builds Blade view with report data, renders via `PDF::loadView()`, returns streamed PDF; both methods enforce the same `DashboardScopeService` scope applied in the web controller

**Files Modified:**
- `routes/web.php` — added `crm.analytics.reports.export` route (`GET reports/export`) under reports prefix; accepts `report` and `format` query params; gated by `crm.reports.view`
- `resources/views/crm/analytics/reports/*.blade.php` (all 9 standard reports) — export placeholder buttons replaced with live export links pointing to `crm.analytics.reports.export` with current filter params passed through
- `tests/Feature/CRM/Analytics/ReportExcelExportTest.php` — 5 tests: Excel download returns 200 with `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, correct Content-Disposition filename, 403 for no-permission, invalid report type returns 422, scope isolation (counsellor export contains own-only rows)
- `tests/Feature/CRM/Analytics/ReportPdfExportTest.php` — 5 tests: PDF download returns 200 with `application/pdf`, correct filename, 403 for no-permission, invalid format returns 422, data content assertion

**Notes:** Export always uses the full (non-paginated) dataset — `ReportService` methods accept a `$paginate = false` flag added in this implementation to switch between paginator and collection return. Scope is re-enforced in `ReportExportService` (not just in the controller) so direct URL access cannot bypass institution isolation. PDF template uses a minimal Blade layout (no sidebar) for clean print output.

---

### AR-021 — API Access to Analytics (Power BI / Tableau)
**Status:** ⏳ Deferred to Sprint 5 (Could Have)
**Planned implementation:**
- `AnalyticsApiController` under `App\Http\Controllers\Api\V1\CRM\Analytics\` — `leadsData()`, `applicationsData()`, `enrolmentsData()`, `funnelData()` endpoints with pagination and field selection.
- Institution-level API token authentication (separate from user session tokens).
- Routes under `/api/v1/crm/analytics` with token middleware.
