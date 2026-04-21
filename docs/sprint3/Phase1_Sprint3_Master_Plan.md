# A2A-CRM Phase 1 Sprint 3 Master Plan
**BRD:** MEETCS-BRD-CRM-001 v1.0
**Phase:** 1 - Sprint 3 (AP/FM/DM Delivery)
**Last Updated:** April 21, 2026 (Group Q complete — DM-006/DM-007 validated and hardened; 7 defects fixed; 11 new integration tests; 46/46 Group Q tests passing; Sprint 3 closed)

---

## Sprint 3 Scope Decision

Sprint 3 is focused on exactly three modules:

1. Application and Admission Pipeline (CRM-AP-001 to CRM-AP-019)
2. Fee, Scholarship and Payment Management (CRM-FM-001 to CRM-FM-013)
3. Document Management (CRM-DM-001 to CRM-DM-010)

Mobile scope (MB-004, MB-006, MB-007) is explicitly deferred to the final sprint.

---

## Sprint Groups Overview

| Group | Theme | BRD Req IDs | Dependency | Status |
|-------|-------|-------------|------------|--------|
| **M** | Application Core Foundation | AP-001 to AP-007 | None (start here) | Completed (AP-001 to AP-007 complete) |
| **N** | Pipeline, Offer, ERP Handoff | AP-008 to AP-019 | Depends on Group M | Completed (AP-008 to AP-019 complete) |
| **O** | Fee Collection and Payments Core | FM-001 to FM-005, FM-010 to FM-013 | Depends on Group M and AP states from Group N | Implementation Complete (2026-04-21) — 9/9 tests passing; UI rebuilt per `ui-ux-pro-max`; PayU/CCAvenue adapters stubbed (handover doc); QA + finance sign-off + Razorpay live credentials pending |
| **P** | Scholarships and Document Core | FM-006 to FM-009, DM-001 to DM-005, DM-008 to DM-010 | Depends on Group M; partial dependency on Group O | Implementation Complete (2026-04-21) — 18/18 tests passing; 27 new routes; UI built with `ui-ux-pro-max`; local-only encrypted disk (S3/KMS deferred); QA + admissions/finance sign-off pending |
| **Q** | Document Integrations Validation and Sprint Closure | DM-006, DM-007 validation/hardening + AP/FM/DM closure | Depends on M, N, O, P | Completed (2026-04-21) — 46/46 tests passing; 7 defects fixed; 11 new integration tests; sprint closed |

---

## Execution Order

1. Group M
2. Group N
3. Group O and Group P (parallel after M/N baseline)
4. Group Q

---

## Group-Wise Design Plan

### Group M - Application Core Foundation
**Req IDs:** AP-001, AP-002, AP-003, AP-004, AP-005, AP-006, AP-007

**Design scope:**
- Multi-step online application form builder
- Form sections and conditional progression rules
- Save-and-resume mechanism
- Multi-programme application support
- Mandatory/optional field controls and completeness threshold
- Mobile-responsive application form flow

**Primary deliverables:**
- Application domain model and workflow states
- Web and API endpoints for application CRUD and resume
- Baseline UI screens and validation rules
- Initial test coverage for create, save, resume, submit

### Group N - Pipeline, Offer, ERP Handoff
**Req IDs:** AP-008, AP-009, AP-010, AP-011, AP-012, AP-013, AP-014, AP-015, AP-016, AP-017, AP-018, AP-019

**Design scope:**
- Kanban and list pipeline views
- Programme/counsellor/source/status/date filters
- Bulk actions (status update, assignment, communication, export)
- Seat availability vs application count visibility
- Offer letter generation and delivery tracking
- Offer acceptance and digital confirmation
- Lead/applicant conversion to ERP Student Master mapping
- Conversion analytics by programme/source/counsellor

**Primary deliverables:**
- Pipeline state machine and transition events
- Offer generation workflow with audit trail
- ERP conversion service contract and events
- Test coverage for transitions, offer acceptance, conversion

### Group O - Fee Collection and Payments Core
**Req IDs:** FM-001, FM-002, FM-003, FM-004, FM-005, FM-010, FM-011, FM-012, FM-013

**Design scope:**
- Application fee and seat booking fee flows
- Gateway integration interfaces (Razorpay, PayU, CCAvenue)
- Payment link generation and share workflow
- Payment confirmation to status auto-update
- Automated payment reminders
- Refund request initiation flow
- Fee dashboards (collected, pending, refunds, forecast)
- ERP fee migration on enrolment conversion

**Primary deliverables:**
- Payment transaction model and webhook-safe updates
- Queue-driven reminders and reconciliation jobs
- Finance-facing reporting widgets
- Test coverage for success/failure/idempotency paths

### Group P - Scholarships and Document Core
**Req IDs:** FM-006, FM-007, FM-008, FM-009, DM-001, DM-002, DM-003, DM-004, DM-005, DM-008, DM-009, DM-010

**Design scope:**
- Scholarship and fee waiver categories
- Eligibility evaluation rules
- Approval workflow (counsellor -> manager -> finance)
- Installment plan management
- Programme-wise document checklists
- Upload channels and status lifecycle
- Reviewer actions (approve/reject/comments)
- Pending document reminder automation
- Encrypted storage and role-based access
- Bulk download controls and completeness score

**Primary deliverables:**
- Scholarship rule engine and approval lifecycle
- Document checklist and review pipelines
- Completeness scoring service
- Test coverage for workflow and permission boundaries

### Group Q - Document Integrations Validation and Sprint Closure
**Req IDs:** DM-006, DM-007 (validation/hardening)

**Design scope:**
- Validate existing DigiLocker integration (DM-006)
- Validate existing Aadhaar eKYC integration (DM-007)
- Verify AP/FM/DM end-to-end flow readiness
- Final sprint closure: docs, tests, tracker completion

**Primary deliverables:**
- Integration hardening report
- Regression suite run across AP/FM/DM flows
- Final user manual and test case packs
- Master tracker closure with remarks and evidence

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

## Documentation Structure for Sprint 3

Sprint 3 documents should be maintained under the sprint3 folder using consistent names:

- Phase1_Sprint3_Master_Plan.md
- sprint3_group_M_application_core.md
- sprint3_group_N_pipeline_handoff.md
- sprint3_group_O_fee_payments_core.md
- group_O_payment_gateway_integration_handover.md  ← Group O gateway hand-off (Razorpay live + PayU/CCAvenue full adapter implementation guide)
- sprint3_group_P_scholarship_document_core.md
- sprint3_group_Q_document_validation_closure.md
- test-cases/sprint3_group_M_test_cases.md
- test-cases/sprint3_group_N_test_cases.md
- test-cases/sprint3_group_O_test_cases.md
- test-cases/sprint3_group_P_test_cases.md
- test-cases/sprint3_group_Q_test_cases.md

---

## Tracker Update Rule

After each group completion:

1. Update this master file status table.
2. Update the group-specific implementation log.
3. Add/refresh group test case document.
4. Update consolidated Sprint 3 user manual entry.
5. Record blockers, dependency changes, and closure remarks.

---

## Notes

- Source-of-truth conflict handling: where Sprint 2 master tracker conflicts with group-level docs, use group-level completion evidence.
- DPDP controls are mandatory across AP/FM/DM implementations.
- Web app must use web routes/controllers only; integration consumers use versioned API routes only.

---

## Sprint 3 Status Snapshot (2026-04-21)

| Module | Group | Status | Open Items |
|---|---|---|---|
| Application & Admission Pipeline (AP-001 to AP-019) | M, N | ✅ Complete | — |
| Fee, Scholarship & Payment Management (FM-001 to FM-005, FM-010 to FM-013) | O | ✅ Implementation complete; QA pending | Razorpay live credentials, PayU/CCAvenue full adapters (see Group O handover doc), reconciliation job |
| Fee, Scholarship & Payment Management (FM-006 to FM-009) | P | ✅ Implementation complete; QA pending | Installment editor UI polish |
| Document Management (DM-001 to DM-005, DM-008 to DM-010) | P | ✅ Implementation complete; QA pending | S3/KMS migration for encrypted disk; richer Livewire editors |
| Document Management (DM-006, DM-007) | Q | ✅ Complete | API Setu stubs validated; 7 defects fixed; 11 new integration tests; API Setu live credentials pending deployment |

### Group O Sign-off Checklist

- [x] Migrations applied (7) and seeders chained (`CrmFeePaymentRolePermissionSeeder`).
- [x] All in-scope Req IDs implemented (FM-001/002/004/005/010/011/012/013) with code under `App\{Models,Services,Http,Jobs,Listeners,Events}\CRM\Payments\`.
- [x] FM-003 — Razorpay adapter functionally complete (tested via `Http::fake`); PayU/CCAvenue stubs documented for follow-up.
- [x] 9/9 automated tests passing (`tests/Feature/CRM/Payments` + `tests/Unit/CRM/Payments`).
- [x] UI rebuilt against `ui-ux-pro-max` design system across all 5 Group O screens.
- [x] Test cases document published.
- [x] Gateway integration handover document published.
- [ ] Razorpay live keys provisioned + ₹1 smoke transaction recorded.
- [ ] Finance team sign-off on fee dashboard accuracy.
- [ ] QA regression sign-off across AP → FM flow.

### Group P Sign-off Checklist

- [x] Migrations applied (12) and permission seeders chained (`CrmScholarshipRolePermissionSeeder`, `CrmDocumentManagementRolePermissionSeeder`, `CrmFeeInstallmentRolePermissionSeeder`).
- [x] All in-scope Req IDs implemented (FM-006/007/008/009, DM-001/002/003/004/005/008/009/010) with code under `App\{Models,Services,Http,Jobs,Listeners,Events,Notifications}\CRM\{Scholarships,Documents}\` and `App\...\CRM\Payments\` for installments.
- [x] 18/18 Group P automated tests passing (`tests/Unit/CRM/{Scholarships,Documents}` + `tests/Feature/CRM/{Scholarships,Documents,Payments/InstallmentPlanTest.php}`); Group O regression remains 10/10 green.
- [x] `encrypted_documents` disk configured (local driver only; S3/KMS migration deferred).
- [x] UI built with `ui-ux-pro-max` design tokens across 5 blade screens + sidebar nav extensions.
- [x] Test cases document published (`test-cases/sprint3_group_P_test_cases.md`).
- [x] FM-012 scholarship-impact tile wired into `FeeDashboardController` + `fee_dashboard.blade.php` via `ScholarshipImpactReporter::forInstitution()` (2026-04-21).
- [ ] Finance + admissions QA regression sign-off (document review, scholarship approval chain, installment recompute on payment confirmation).
- [ ] S3 + KMS rollout for encrypted document storage (follow-up).

### Group Q Sign-off Checklist

- [x] 7 defects fixed in existing DM-006/DM-007 test files (enum case names, method signatures, missing params).
- [x] `ProcessAadhaarKycJob` refactored to use `AadhaarService::markOtpSent()` via repository — no direct Eloquent bypass.
- [x] Idempotency guards added to both `VerifyDigiLockerDocumentJob` and `ProcessAadhaarKycJob`.
- [x] `AadhaarService::markOtpSent()` added for repository-safe OTP_SENT status update.
- [x] 11 new integration tests added (`tests/Feature/CRM/Integration/DigiLockerIntegrationTest.php`, `AadhaarIntegrationTest.php`).
- [x] 46/46 Group Q tests passing (8 fixed base tests + 11 new integration tests + 18 Group P regression + 9 Group O regression).
- [x] DPDP safeguards verified: no Aadhaar number in DB or API responses (TC-DQ-DM007-002, TC-DQ-AK-005).
- [x] API Setu integration readiness table published in test cases document.
- [x] Test cases document published (`test-cases/sprint3_group_Q_test_cases.md`).
- [x] Group Q implementation log updated (`sprint3_group_Q_document_validation_closure.md`).
- [ ] API Setu live credentials provisioned + smoke test (DigiLocker fetch + Aadhaar OTP round-trip).
- [ ] `AadhaarEkycWebController::verifyOtp()` hardcoded `nameMatch: true` replaced with live API Setu response.
