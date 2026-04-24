# A2A-CRM Phase 1 Sprint 4 Master Plan
**BRD:** MEETCS-BRD-CRM-001 v1.0
**Phase:** 1 - Sprint 4 (TF / SP / AG / AR / SA / CR / AL Delivery)
**Last Updated:** April 23, 2026 (Sprint 4 CLOSED — all groups R, S, U, V, W completed; all Must Have and Should Have delivered; AR-021 Could Have deferred to Sprint 5)

---

## Sprint 4 Scope Decision

Sprint 4 closes all remaining Must-Have functional modules for Phase 1 v1.0:

1. Task, Activity and Follow-up Management (CRM-TF-001 to CRM-TF-009)
2. Student Applicant Portal and Self-Service (CRM-SP-001 to CRM-SP-008)
3. Agent and Channel Partner Portal (CRM-AG-001 to CRM-AG-005, CRM-AG-007)
4. Analytics, Dashboards and Reporting (CRM-AR-001 to CRM-AR-021)
5. System Administration and Configuration (CRM-SA-001 to CRM-SA-006, SA-008, SA-009, SA-012)
6. Compliance and Regulatory Requirements (CRM-CR-001 to CRM-CR-010)
7. Alumni Lifecycle Bridge — Must Have only (CRM-AL-001)

**Already completed in prior sprints and excluded from Sprint 4:**
- Marketing Automation (MA-001–MA-010) — completed in Sprint 2 Group H
- Remaining Telecalling items (TC-007, TC-008, TC-009) — completed in Sprint 2 Group J

**Deferred to Sprint 5:**
- Mobile App (MB-001 to MB-008)
- ERP Integration Layer remaining (EI-001 to EI-007, EI-009)
- Alumni Extended (AL-002, AL-003, AL-004 — Should Have / Could Have)
- AI Remaining (AI-001, AI-007)
- NFR implementation (performance, scaling, security hardening)

---

## Sprint Groups Overview

| Group | Theme | BRD Req IDs | Dependency | Status |
|-------|-------|-------------|------------|--------|
| **R** | Task, Activity and Follow-up Management | TF-001 to TF-009 | EC (Sprint 1) | ✅ Completed (2026-04-21) |
| **S** | Student Applicant Portal and Self-Service | SP-001 to SP-008 | AP, FM, DM (Sprint 3) | ✅ Completed (2026-04-21) |
| **U** | Agent and Channel Partner Portal | AG-001–AG-005, AG-007 | LC, CC (Sprint 1); AG-006, AG-008 done in Sprint 2 | ✅ Completed (2026-04-22) |
| **V** | Analytics, Dashboards and Reporting | AR-001 to AR-021 | All prior sprints | ✅ Completed (2026-04-23) — AR-021 Could Have deferred to Sprint 5 |
| **W** | System Administration, Compliance and Sprint Closure | SA-001–006, SA-008, SA-009, SA-012; CR-001–010; AL-001 | All prior sprints | ✅ Completed (2026-04-23) |

---

## Execution Order

1. Group R and Group S (parallel — independent start)
2. Group U (parallel with S after R foundations available)
3. Group V (depends on all prior sprint data models)
4. Group W (sprint closure — SA + CR + AL)

---

## Group-Wise Design Plan

### Group R — Task, Activity and Follow-up Management
**Req IDs:** TF-001, TF-002, TF-003, TF-004, TF-005, TF-006, TF-007, TF-008, TF-009

**Design scope:**
- Counsellor task creation (call, email, WhatsApp, meeting, document review) linked to lead record
- Auto-creation of follow-up tasks based on inactivity rules
- Counsellor daily task dashboard sorted by priority and due time
- Overdue task flagging with escalation rules
- Task completion requiring disposition (Reached-Interested, Not Reachable, Call Back, etc.)
- Manager team-level task and activity view
- Real-time counsellor activity feed for managers
- Bulk task assignment and reassignment
- Calendar view of tasks (daily, weekly, monthly)

**Primary deliverables:**
- Task domain model with type and disposition enums
- Auto-task rule engine and scheduler job
- Counsellor and manager dashboard views
- Calendar component (Livewire)
- Test coverage for creation, auto-trigger, escalation, and disposition flows

### Group S — Student Applicant Portal and Self-Service
**Req IDs:** SP-001, SP-002, SP-003, SP-004, SP-005, SP-006, SP-007, SP-008

**Design scope:**
- Branded, mobile-responsive applicant portal per institution
- OTP-based authentication (mobile and email)
- Portal dashboard (application status, document checklist, payment history, appointments)
- Applicant chat with assigned counsellor (via CC-021 unified inbox)
- Downloadable offer letter, admission confirmation, payment receipts (uses AP-012 PDF generator)
- Multiple simultaneous applications support (reuses AP-005 model)
- Seamless ERP portal transition on enrolment (same credentials, no re-registration)
- Institutional branding (logo, colours, domain)

**Primary deliverables:**
- Portal authentication and OTP flow
- Branded portal layout with institution theming
- Applicant-facing dashboard components
- Download controller wired to existing PDF generator
- ERP token bridge service
- Test coverage for auth, dashboard data, download, and branding

### Group U — Agent and Channel Partner Portal
**Req IDs:** AG-001, AG-002, AG-003, AG-004, AG-005, AG-007

**Design scope:**
- Agent profile management (contact details, agreement terms, active programmes, performance history)
- Unique referral link and code per agent for lead attribution (extends LC-014 Source field)
- Agent portal (submit leads, track lead status, view conversion dashboard)
- Commission structures per agent agreement (per enrolment, per application, percentage of fee)
- Commission accrual auto-calculation on enrolment confirmation
- Agent performance report (leads submitted, conversions, revenue, commissions)

**Primary deliverables:**
- Agent and commission models
- Referral tracking and attribution service
- Agent-facing portal with lead submission and dashboard
- Commission accrual observer on enrolment event
- Test coverage for referral attribution, commission calculation, and portal access

### Group V — Analytics, Dashboards and Reporting
**Req IDs:** AR-001 to AR-021

**Design scope:**
- Institution admissions dashboard (leads, applications, offers, enrolments, revenue — by programme, campus, source, period)
- Counsellor performance dashboard
- Marketing campaign dashboard (spend vs leads, CPL, CPE, channel ROI)
- Admissions funnel visualisation with stage-wise conversion and drop-off analysis
- Seat availability vs confirmed enrolments (real-time)
- Director/Management executive dashboard (KPI tiles, trend charts)
- Role-based dashboard data scoping (counsellor / manager / director)
- Date range filter and drill-down to individual lead records
- Standard reports: enquiry register, counsellor activity, application status, source effectiveness, lost lead analysis, fee collection, document compliance, year-on-year comparison, agent performance
- Export to Excel and PDF for all reports
- Custom report builder (fields, filters, grouping, aggregations)
- Scheduled report delivery via email
- API access to analytics (Power BI / Tableau — Could Have)

**Primary deliverables:**
- Dashboard controller with role-scoped data services
- Funnel analytics service
- Report controller with 9 standard report methods
- Report export service (Laravel Excel + DomPDF)
- Custom report builder model and service
- Scheduled report job
- Test coverage for dashboard scoping, report output, and export formats

### Group W — System Administration, Compliance and Sprint Closure
**Req IDs:** SA-001–SA-006, SA-008, SA-009, SA-012 | CR-001–CR-010 | AL-001

**Design scope:**

**System Administration:**
- Multi-institution support with complete data segregation (tenancy middleware)
- Multi-campus support within a single institution
- Academic year and admission cycle management with rollover
- Full audit trail for all CRM data changes
- Data import and export (leads, applications, contacts) in CSV/Excel
- System configuration (business hours, timezone, locale, institution branding)
- Custom field management for leads, applications, and students (extends EC-005 from Sprint 1)
- Email and notification template management
- Backup and restore with configurable frequency

**Compliance (DPDP Act 2023 / TRAI):**
- Explicit consent capture at point of lead creation
- Consent records with timestamp, IP address, and form version
- Opt-out/unsubscribe honoured within 24h and logged
- Right-to-access: applicant data copy request from student portal
- Right-to-erasure: PII anonymisation within 30 days
- Data residency enforcement (India-hosted servers)
- Call recording consent notification (extends CC-018)
- DLT-registered SMS template enforcement (extends CC-008)
- Data Processing Agreement available for institutions
- Breach notification workflow (72h alert and documentation)

**Alumni:**
- AL-001: Auto-populate alumni pipeline from enrolled students on programme completion

**Primary deliverables:**
- TenancyService with institution and campus scope middleware
- AuditObserver wired globally to all CRM models
- AcademicYear model with cycle rollover command
- ConsentRecord model and opt-out job
- PII erasure job and gdpr:erase command
- BreachNotificationJob with SecurityIncident model
- AlumniPipelineService triggered on graduation event
- Test coverage for tenancy isolation, audit trail, consent lifecycle, and erasure

---

## Mandatory Deliverables After Each Group

1. User Manual
   - Feature usage steps
   - Role-based usage notes
   - Screenshots where applicable

2. Test Cases
   - Scenario ID
   - Preconditions
   - Steps
   - Expected result
   - Actual result and status

3. Master Tracker Update
   - Mark status: Planned / In Progress / Completed / Blocked
   - Update dependencies and remarks
   - Add completion date and evidence note

---

## Documentation Structure for Sprint 4

Sprint 4 documents should be maintained under the sprint4 folder using consistent names:

- Phase1_Sprint4_Master_Plan.md
- sprint4_group_R_task_followup.md
- sprint4_group_S_student_portal.md
- sprint4_group_U_agent_portal.md
- sprint4_group_V_analytics_reporting.md
- sprint4_group_W_sysadmin_compliance.md
- test-cases/sprint4_group_R_test_cases.md
- test-cases/sprint4_group_S_test_cases.md
- test-cases/sprint4_group_U_test_cases.md
- test-cases/sprint4_group_V_test_cases.md
- test-cases/sprint4_group_W_test_cases.md

---

## Tracker Update Rule

After each group completion:

1. Update this master file status table.
2. Update the group-specific implementation log.
3. Add/refresh group test case document.
4. Update consolidated Sprint 4 user manual entry.
5. Record blockers, dependency changes, and closure remarks.

---

## Notes

- All web flows must use web controllers and Blade/Livewire views; external consumers use versioned API routes only.
- DPDP compliance controls are mandatory across all Group W CR items and must be verified in Group R, S, U, V where personal data is handled.
- Role-based data scoping must be applied at service layer, not just at view layer, to prevent data leakage across institutions and user roles.
- Group V analytics must query read-optimised views or aggregates; avoid N+1 on dashboard loads.
- Group W tenancy middleware must be verified to intercept all model queries before Group V dashboards go live.

---

## Sprint 4 Status Snapshot (2026-04-21)

| Module | Group | Status | Open Items |
|---|---|---|---|
| Task, Activity and Follow-up (TF-001 to TF-009) | R | ✅ Completed | 2026-04-21 |
| Student Applicant Portal (SP-001 to SP-008) | S | ✅ Completed | 2026-04-21 |
| Agent and Channel Partner Portal (AG-001–005, AG-007) | U | ✅ Completed | 2026-04-22 |
| Analytics, Dashboards and Reporting (AR-001 to AR-021) | V | ✅ Completed | All Must Have (AR-001–017, AR-019) and Should Have (AR-018, AR-020) completed (2026-04-23); AR-021 Could Have deferred to Sprint 5 |
| System Admin, Compliance, Alumni (SA, CR, AL-001) | W | ✅ Completed | 2026-04-23 |

---

## Sprint 4 BRD Coverage Tracker

| Req ID | Priority | Group | Status |
|--------|----------|-------|--------|
| CRM-TF-001 | Must Have | R | ✅ Completed |
| CRM-TF-002 | Must Have | R | ✅ Completed |
| CRM-TF-003 | Must Have | R | ✅ Completed |
| CRM-TF-004 | Must Have | R | ✅ Completed |
| CRM-TF-005 | Must Have | R | ✅ Completed |
| CRM-TF-006 | Must Have | R | ✅ Completed |
| CRM-TF-007 | Must Have | R | ✅ Completed |
| CRM-TF-008 | Must Have | R | ✅ Completed |
| CRM-TF-009 | Must Have | R | ✅ Completed |
| CRM-SP-001 | Must Have | S | ✅ Completed |
| CRM-SP-002 | Must Have | S | ✅ Completed |
| CRM-SP-003 | Must Have | S | ✅ Completed |
| CRM-SP-004 | Should Have | S | ✅ Completed |
| CRM-SP-005 | Must Have | S | ✅ Completed |
| CRM-SP-006 | Must Have | S | ✅ Completed |
| CRM-SP-007 | Must Have | S | ✅ Completed |
| CRM-SP-008 | Must Have | S | ✅ Completed |
| CRM-AG-001 | Must Have | U | ✅ Completed |
| CRM-AG-002 | Must Have | U | ✅ Completed |
| CRM-AG-003 | Must Have | U | ✅ Completed |
| CRM-AG-004 | Must Have | U | ✅ Completed |
| CRM-AG-005 | Must Have | U | ✅ Completed |
| CRM-AG-007 | Must Have | U | ✅ Completed |
| CRM-AR-001 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-002 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-003 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-004 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-005 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-006 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-007 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-008 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-009 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-010 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-011 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-012 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-013 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-014 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-015 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-016 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-017 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-018 | Should Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-019 | Must Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-020 | Should Have | V | ✅ Completed (2026-04-23) |
| CRM-AR-021 | Could Have | V | ⏳ Pending |
| CRM-SA-001 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-002 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-003 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-004 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-005 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-006 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-008 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-009 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-SA-012 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-001 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-002 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-003 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-004 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-005 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-006 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-007 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-008 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-009 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-CR-010 | Must Have | W | ✅ Completed (2026-04-23) |
| CRM-AL-001 | Must Have | W | ✅ Completed (2026-04-23) |

---

## Sprint 4 Closure Remarks — 2026-04-23

**Sprint 4 is CLOSED.** All five groups (R, S, U, V, W) have been implemented end-to-end.

### Group W Delivery Summary

**Phases completed:**
1. **Migrations** — 9 new tables: `academic_years`, `system_configs`, `notification_templates`, `backup_logs`, `opt_out_logs`, `data_access_requests`, `pii_erasure_requests`, `security_incidents`, `alumni_pipeline`
2. **Enums** — 9 enums across `Admin/`, `Compliance/`, `Alumni/` namespaces; all implement `label()`, `badgeClass()`, `color()`
3. **Models** — 9 Eloquent models; Admin models use `InstitutionScope` + `AuditObserver`; Alumni model auto-tracked; Compliance models scoped to institution
4. **Traits + Observer** — `HasInstitutionScope`, `ObservesAudit`, `HasCampusScope`; `GraduationObserver` fires on `ApplicationStatus::ENROLLED`
5. **Services** — 14 services: TenancyService, AcademicYearService, SystemConfigService, NotificationTemplateService, DataImportService, DataExportService (Admin); ConsentService, OptOutService, DataAccessService, PiiErasureService, DataResidencyService, DltTemplateValidatorService, BreachNotificationService (Compliance); AlumniPipelineService (Alumni)
6. **Jobs** — 7 queued jobs across `crm-admin`, `crm-compliance`, `crm-alumni` queues; 3 artisan commands; 3 scheduled entries in `routes/console.php`
7. **Controllers** — 10 Admin + 6 Compliance + 1 Alumni + 3 Portal controllers; all authorise via Policy
8. **Policies** — 6 policies covering Institution, Campus, AcademicYear, SystemConfig, AuditLog, Compliance, Alumni
9. **Middleware** — `data.residency` (enforces India storage in prod/staging) + `dlt.sms.check` (advisory DLT warning); registered as aliases in `bootstrap/app.php`
10. **Routes** — ~75 routes added to `routes/web.php` under `crm.admin.*`, `crm.compliance.*`, `crm.alumni.*` prefixes
11. **Views** — 32 Blade views (17 Admin + 15 Compliance/Alumni/Portal); all use responsive table wrapper pattern with `card overflow-hidden` + `overflow-x-auto` + `table-th`/`table-td`
12. **Sidebar** — System Administration (9 links) + Compliance (6 links) + Alumni (1 link) sections added to `crm.blade.php` layout with `@can` guards
13. **Seeders** — 4 seeders: SystemAdmin, Compliance, Alumni role-permission seeders + InstitutionSeeder (dev-only); registered in DatabaseSeeder
14. **Service Providers** — CrmAdminServiceProvider, CrmComplianceServiceProvider, CrmAlumniServiceProvider; registered in `bootstrap/providers.php`
15. **Consent wiring** — ConsentService integrated into LeadService::create() when `$dto->consentGiven === true`
16. **Tests** — 13 Pest test files (5 Unit + 8 Feature); ~49 test cases covering all Group W req IDs
17. **Documentation** — `sprint4_group_W_test_cases.md` (40 test cases), `user-manual-group-W.md` (full role-based manual)

### Deferred to Sprint 5
- CRM-AR-021 (Could Have) — advanced report scheduling
- CRM-AL-002, AL-003, AL-004 — extended alumni features
- Mobile App (MB-001–MB-008)
- ERP Integration remaining (EI-001–EI-007, EI-009)
- NFR implementation (performance, scaling, security hardening)
