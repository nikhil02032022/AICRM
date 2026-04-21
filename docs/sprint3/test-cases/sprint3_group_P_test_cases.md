# Sprint 3 — Group P Test Cases (Scholarships & Document Core)

**BRD Req IDs:** CRM-FM-006 to CRM-FM-009, CRM-DM-001 to CRM-DM-005, CRM-DM-008 to CRM-DM-010

**Suite total:** 18/18 passing (10 feature, 10 unit/feature mixed across 6 files).

---

## Scholarship Eligibility (FM-007)

### TC-FM-P-001 — Category with no rules is always eligible
- **Pre:** Active `ScholarshipCategory` for the application's programme with zero rules.
- **Steps:** `ScholarshipEligibilityEvaluator::evaluate($application)`.
- **Expected:** Collection contains that category.
- **Auto:** `tests/Unit/CRM/Scholarships/ScholarshipEligibilityEvaluatorTest::it returns categories with no rules`.

### TC-FM-P-002 — Programme scoping respected
- **Pre:** Category created for a *different* programme id.
- **Steps:** Evaluate for an application in programme A.
- **Expected:** Collection is empty.
- **Auto:** Same file, `it respects programme scoping`.

### TC-FM-P-003 — Inactive categories skipped
- **Pre:** Matching category with `is_active = false`.
- **Steps:** Evaluate.
- **Expected:** Empty collection.
- **Auto:** Same file, `it ignores inactive categories`.

### TC-FM-P-004 — Operator semantics (gte)
- **Pre:** Rule `lead.lead_score gte 85`; applicant score 90.
- **Steps:** Evaluate, then change rule value to 95, re-evaluate.
- **Expected:** First → 1 match; second → 0 matches.
- **Auto:** Same file, `it only matches when rule operator passes`.

### TC-FM-P-005 — Non-whitelisted attribute fails closed
- **Pre:** Rule references `application.not_whitelisted`.
- **Steps:** Evaluate.
- **Expected:** Category excluded (security-conservative default).
- **Auto:** Same file, `it excludes a category whose only rule references a non-whitelisted attribute`.

---

## Scholarship Approval Chain (FM-008)

### TC-FM-P-006 — Happy path counsellor → manager → finance
- **Pre:** Draft award, current_stage = counsellor.
- **Steps:** `submit`, `approve(MANAGER)`, `approve(FINANCE)`.
- **Expected:** Status transitions DRAFT → COUNSELLOR_SUBMITTED → MANAGER_APPROVED → FINANCE_APPROVED; `finance_approved_at` populated.
- **Auto:** `tests/Feature/CRM/Scholarships/ScholarshipAwardLifecycleTest::it advances through all three stages on the happy path`.

### TC-FM-P-007 — Rejection at manager stage
- **Pre:** Award submitted by counsellor; awaiting manager.
- **Steps:** `reject(MANAGER, 'not qualifying')`.
- **Expected:** Status REJECTED; `rejection_reason = 'not qualifying'`.
- **Auto:** Same file, `it rejects at manager stage`.

### TC-FM-P-008 — Stage mismatch rejected
- **Pre:** Award awaiting manager.
- **Steps:** Attempt `approve(FINANCE)` directly.
- **Expected:** `DomainException`.
- **Auto:** Same file, `it refuses decision at wrong stage`.

---

## Installments (FM-009)

### TC-FM-P-009 — Plan schedule must sum to 100
- **Pre:** n/a.
- **Steps:** Create plan with schedule 40 + 50 (=90).
- **Expected:** `InvalidArgumentException`.
- **Auto:** `tests/Feature/CRM/Payments/InstallmentPlanTest::it rejects plans whose schedule does not sum to 100`.

### TC-FM-P-010 — Apply plan to application
- **Pre:** Plan with two rows 50/50, total 100000.
- **Steps:** `ApplicationInstallmentService::applyPlan`.
- **Expected:** Two `ApplicationInstallmentSchedule` rows, each 50000, status PENDING.
- **Auto:** Same file, `it applies a plan to an application and produces schedule rows`.

---

## Document Storage & Encryption (DM-008)

### TC-DM-P-001 — Encrypted round-trip
- **Pre:** Fake `encrypted_documents` disk.
- **Steps:** Store file via `DocumentEncryptionManager::store`; read back.
- **Expected:** Plaintext returned matches original; raw on-disk bytes differ.
- **Auto:** `tests/Unit/CRM/Documents/DocumentEncryptionManagerTest::it round-trips file contents through encryption`.

### TC-DM-P-002 — Delete removes encrypted file
- **Steps:** Store then delete via manager.
- **Expected:** File no longer exists on disk.
- **Auto:** Same file, `it deletes encrypted files`.

---

## Document Upload & Review (DM-002, DM-003, DM-004)

### TC-DM-P-003 — Upload → approve → reject lifecycle
- **Pre:** Application with checklist item (max 1024 KB, PDF only).
- **Steps:** Upload PDF (within size); approve; reject with reason.
- **Expected:** Status transitions SUBMITTED → VERIFIED → REJECTED; storage path populated and encrypted on disk.
- **Auto:** `tests/Feature/CRM/Documents/ApplicationDocumentTest::it uploads, encrypts, approves, and rejects documents`.

### TC-DM-P-004 — Over-size upload rejected
- **Steps:** Upload 2048 KB file for a 1024 KB item.
- **Expected:** `DomainException`.
- **Auto:** Same file, `it rejects over-size uploads`.

### TC-DM-P-005 — Disallowed mime type rejected
- **Steps:** Upload `.exe` (`application/octet-stream`) where only PDF allowed.
- **Expected:** `DomainException`.
- **Auto:** Same file, `it rejects disallowed mime types`.

---

## Completeness Score (DM-010)

### TC-DM-P-006 — Zero when no docs verified
- **Pre:** Checklist with 2 mandatory + 1 optional items; no documents.
- **Steps:** `DocumentCompletenessCalculator::scoreFor($application)`.
- **Expected:** 0.0.
- **Auto:** `tests/Unit/CRM/Documents/DocumentCompletenessCalculatorTest::it returns 0 when no docs verified`.

### TC-DM-P-007 — Mandatory weighted heavier than optional
- **Pre:** Only the optional item verified.
- **Steps:** Invalidate cache → score.
- **Expected:** Score between 10 and 12 (0.25 / 2.25 ≈ 11.11%).
- **Auto:** Same file, `it weights mandatory items heavier than optional`.

### TC-DM-P-008 — 100% when all items verified
- **Pre:** All three items verified.
- **Steps:** Invalidate cache → score.
- **Expected:** 100.0.
- **Auto:** Same file, `it reaches 100 when all mandatory + optional verified`.

---

## Manual / Integration checks (pending QA sign-off)

### TC-P-MANUAL-001 — Reminder dispatch command
- **Steps:** Seed a pending document with `DocumentReminder` scheduled_for <= now; run `php artisan crm:documents:dispatch-reminders`.
- **Expected:** `SendDocumentReminderJob` dispatched; reminder row transitions to SENT; console output reports dispatched count.

### TC-P-MANUAL-002 — Scholarship escalation sweep
- **Steps:** Update a COUNSELLOR_SUBMITTED award to have `updated_at` older than `CRM_SCHOLARSHIP_SLA_HOURS`; run `php artisan crm:scholarships:dispatch-escalations`.
- **Expected:** `DispatchApprovalEscalationJob` dispatched; `ScholarshipStageAdvanced` listener re-notifies approvers.

### TC-P-MANUAL-003 — Bulk download lifecycle
- **Steps:** Upload documents across 2 applications in programme X; request programme_batch bulk download.
- **Expected:** `DocumentBulkDownloadJob` transitions QUEUED → PROCESSING → READY; zip downloadable until `expires_at`, then 410 Gone.

### TC-P-MANUAL-004 — Document intake API
- **Steps:** POST `/api/v1/crm/documents/intake` with multipart PDF and a checklist item code.
- **Expected:** 201 JSON `{data: {uuid, status: "submitted"}}`; file encrypted on disk.

### TC-P-MANUAL-005 — Waiver recomputes installments
- **Steps:** Apply an installment plan to an application; submit + approve a ₹10 000 scholarship award through all three stages.
- **Expected:** Open installment rows reduced pro-rata by ₹10 000 (earliest first), remainder carried forward until consumed.

### TC-P-MANUAL-006 — Permission boundaries
- **Steps:** Log in as counsellor; attempt to hit `/crm/scholarships/categories` (manage), `/crm/documents/checklists` (manage), `/crm/payments/installments` (manage).
- **Expected:** 403 for each. Counsellor *can* submit scholarship awards, upload documents, and apply installment plans.

### TC-P-MANUAL-007 — Institution isolation
- **Steps:** Authenticate as a user in institution A; attempt to read a scholarship award or document belonging to institution B (via uuid in URL).
- **Expected:** 404 (InstitutionScope filters it out).
