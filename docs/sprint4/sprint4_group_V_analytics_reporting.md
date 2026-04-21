# Sprint 4 - Group V: Analytics, Dashboards and Reporting

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** V
**Module:** Analytics, Dashboards and Reporting
**Req IDs:** CRM-AR-001 to CRM-AR-021
**Status:** Pending
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

1. AR-001 to AR-021 marked completed (or Should Have / Could Have noted) in master tracker.
2. ~35 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. QA sign-off recorded.

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
| AR-018 | Custom report builder | `CustomReportBuilderService`, `CustomReportController` |
| AR-019 | Export to Excel and PDF | `ReportExportService`, `ReportExportController` |
| AR-020 | Scheduled report delivery | `ScheduledReportJob`, `ReportScheduleController` |
| AR-021 | API analytics access (Power BI / Tableau) | `AnalyticsApiController` under `/api/v1/crm/analytics` |

---

## Security Checklist

- [ ] All dashboard routes protected by `auth` and role-specific `permission` middleware.
- [ ] `DashboardScopeService` resolves scope at service layer; policy double-checks in controllers.
- [ ] Custom report builder field whitelist validated server-side; no raw SQL accepted.
- [ ] AR-021 API uses institution-level API token (separate from user session tokens).
- [ ] Scheduled report job validates recipient email list ownership before sending.
- [ ] Excel and PDF exports do not expose cross-institution data (scope enforced in query before export).

---

## Implementation Log

*(To be updated as implementation progresses)*
