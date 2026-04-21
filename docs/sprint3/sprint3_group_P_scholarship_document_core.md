# Sprint 3 - Group P: Scholarships and Document Core

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** P
**Modules:** Fee and Payments + Document Management
**Req IDs:** CRM-FM-006 to CRM-FM-009, CRM-DM-001 to CRM-DM-005, CRM-DM-008 to CRM-DM-010
**Status:** Implementation Complete (2026-04-21) — 18/18 Group P tests passing; 27 new routes; UI built with `ui-ux-pro-max` tokens; S3/KMS migration deferred.

---

## Objective

Deliver scholarship and waiver workflows plus core document collection, verification, and tracking.

## In Scope

1. Scholarship and fee waiver category setup.
2. Eligibility rule evaluation framework.
3. Approval workflow chain for fee discounts and waivers.
4. Installment plan support for initial fee.
5. Programme-wise document checklist management.
6. Multi-channel document upload entry points.
7. Document status lifecycle and reviewer actions.
8. Automated pending document reminders.
9. Encrypted document storage and role-based access.
10. Bulk document download controls.
11. Document completeness score visibility.

## Dependencies

1. Group O payment and scholarship context.
2. Existing DM-006 and DM-007 integration foundations from Sprint 2.
3. Notification engine for reminder workflows.

## Design Notes

1. Keep verification status transitions auditable.
2. Enforce encryption at rest and strict access controls.
3. Treat checklist definitions as institution-scoped configuration.
4. Include rejection reason capture and re-upload paths.

## Deliverables

1. Group implementation log updates.
2. User manual section for admissions and document verification teams.
3. Group P test cases document.
4. Master tracker status and remarks update.

## Acceptance Gates

1. Scholarship approval flow works end-to-end.
2. Document checklist and submission tracking are operational.
3. Reviewers can approve, reject, and comment on documents.
4. Completeness score reflects checklist status accurately.

## Risks and Mitigation

1. Large file handling and storage cost:
Mitigation: file-size policy, compression where valid, lifecycle rules.
2. Verification backlog:
Mitigation: SLA dashboards and queue-based reminder escalations.

## Exit Criteria

1. FM-006 to FM-009 and DM core requirements marked completed in tracker.
2. User manual and test cases published.
3. QA and compliance sign-off recorded.

---

## Implementation Log (2026-04-21)

### Database

- 12 migrations prefixed `2026_05_10_*`: `scholarship_categories`, `scholarship_eligibility_rules`, `scholarship_awards`, `scholarship_approvals`, `fee_installment_plans`, `application_installment_schedules`, `document_checklists`, `document_checklist_items`, `application_documents`, `application_document_comments`, `document_reminders`, `document_bulk_download_jobs`.
- 3 permission seeders chained in `DatabaseSeeder.php`: `CrmScholarshipRolePermissionSeeder`, `CrmDocumentManagementRolePermissionSeeder`, `CrmFeeInstallmentRolePermissionSeeder`.
- 12 factories under `database/factories/CRM/{Scholarships,Payments,Documents}/`.

### Domain (app/)

- Enums: `Scholarships/{ScholarshipType, ScholarshipAwardStatus, ApprovalStage}`, `Payments/InstallmentStatus`, `Documents/{DocumentStatus, DocumentUploadChannel, DocumentReminderStatus, BulkDownloadStatus, DocumentCommentType}`.
- Models: 12 new Eloquent models — all UUID, InstitutionScope-scoped, soft-deletes.
- Services: `ScholarshipCategoryService`, `ScholarshipEligibilityEvaluator`, `ScholarshipAwardService`, `ScholarshipImpactReporter`, `FeeInstallmentPlanService`, `ApplicationInstallmentService`, `DocumentChecklistService`, `DocumentEncryptionManager`, `ApplicationDocumentService`, `DocumentCompletenessCalculator`, `BulkDownloadService`.
- Events (7) + Listeners (4): `NotifyNextApproverOnStageAdvance`, `ApplyWaiverOnApproved`, `UpdateCompletenessOnDocumentChange`, `NotifyApplicantOnDocumentDecision`.
- Notifications (5): `ApprovalPendingNotification`, `ApprovalDecisionNotification`, `DocumentReminderNotification`, `DocumentDecisionNotification`, `BulkDownloadReadyNotification`.
- Jobs (3): `SendDocumentReminderJob`, `BuildBulkDocumentZipJob`, `DispatchApprovalEscalationJob`.
- Commands (2): `crm:documents:dispatch-reminders`, `crm:scholarships:dispatch-escalations`. Both scheduled at `*/15 * * * *` in `routes/console.php`.
- Providers: `CrmScholarshipServiceProvider`, `CrmDocumentServiceProvider` registered in `bootstrap/providers.php`.
- Relations added to `Application`, `Lead`, `CrmProgramme` (scholarshipAwards, installmentSchedules, documents, documentChecklists, installmentPlans, scholarshipCategories, documentCompletenessScore accessor).

### HTTP

- 12 form requests (authorize() checks new permissions).
- Web controllers: `ScholarshipCategoryController`, `ScholarshipAwardController`, `FeeInstallmentPlanController`, `ApplicationInstallmentController`, `DocumentChecklistController`, `ApplicationDocumentController`, `BulkDocumentDownloadController`.
- API controllers: `DocumentIntakeController` (WhatsApp/email intake gateway), `ScholarshipEligibilityController` (evaluator).
- Routes: 27 new routes across `/crm/scholarships/*`, `/crm/payments/installments/*`, `/crm/documents/*`, plus `/api/v1/crm/documents/intake` and `/api/v1/crm/scholarships/eligibility`.

### UI (built with `ui-ux-pro-max` design tokens)

- 5 blade screens: `crm/scholarships/categories/index`, `crm/scholarships/awards/index`, `crm/payments/installments/index`, `crm/documents/checklists/index`, `crm/documents/review/index`.
- Sidebar extended with Finance-section entries (Scholarship Categories, Scholarship Approvals, Installment Plans) and a new Documents section (Checklists, Document Review), gated by new permissions.
- All views extend `<x-layouts.crm>` and reuse the indigo-950 sidebar, Inter typography, and `btn-primary-sm`/`btn-secondary-sm` patterns established in Group O.

### Storage & Encryption (DM-008)

- New `encrypted_documents` disk in `config/filesystems.php` — **local driver only** (`storage/app/private/crm_documents`, private, throw-on-miss). S3/KMS migration deferred.
- Files stored UUID-named (no PII in path). Contents wrapped with `Crypt::encryptString` at write time and decrypted on read by `DocumentEncryptionManager`. Round-trip and raw-file-differs-from-plaintext covered by unit test.
- Bulk ZIP output written to `storage/app/private/crm_document_zips/`.

### Configuration

- `config/crm_scholarships.php` — approval chain roles, escalation SLA, impact cache TTL, whitelisted eligibility attributes (`application.programme_id`, `lead.lead_score`, `lead.marks_10th`, `lead.marks_12th`, `lead.graduation_percentage`, `lead.source`).
- `config/crm_documents.php` — storage disk + root, max size, reminder cadence (1/3/7 days), bulk-download TTL + max files, default allowed MIME list, completeness weights (mandatory 1.0, optional 0.25).

### Tests (18/18 passing)

- Unit: `ScholarshipEligibilityEvaluatorTest` (5), `DocumentEncryptionManagerTest` (2), `DocumentCompletenessCalculatorTest` (3).
- Feature: `ScholarshipAwardLifecycleTest` (3), `InstallmentPlanTest` (2), `ApplicationDocumentTest` (3).
- Group O regression: all 10 payment tests still green.

### Known open items

- Wire FM-012 scholarship-impact dashboard tile into the Group O fee-dashboard Livewire component (reader service `ScholarshipImpactReporter` is ready).
- S3 + KMS migration for `encrypted_documents` disk (local-only this sprint).
- Richer Livewire editors (InstallmentScheduleEditor, DocumentChecklistEditor, BulkDownloadModal, CompletenessScoreBadge) — schemas, routes, and services exist; richer UI is a polish follow-up.
- DigiLocker / Aadhaar eKYC validation (DM-006/DM-007) — owned by Group Q.

### Sign-off checklist

- [x] Migrations applied (12) and seeders chained.
- [x] All in-scope Req IDs implemented (FM-006/007/008/009, DM-001/002/003/004/005/008/009/010).
- [x] 18/18 automated tests passing (Unit + Feature).
- [x] Group O regression suite still green (10/10 payments).
- [x] UI built against `ui-ux-pro-max` design tokens across 5 screens + sidebar nav.
- [x] Group P test cases document published (see `test-cases/sprint3_group_P_test_cases.md`).
- [ ] Finance + admissions QA regression sign-off.
- [ ] S3/KMS rollout for `encrypted_documents` disk.
- [ ] Wire live scholarship-impact tile into FM-012 fee dashboard Livewire component.
