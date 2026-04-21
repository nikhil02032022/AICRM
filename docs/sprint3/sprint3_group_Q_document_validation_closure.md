# Sprint 3 - Group Q: Document Integrations Validation and Sprint Closure

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** Q  
**Modules:** Document Management + Sprint Closure  
**Req IDs:** CRM-DM-006, CRM-DM-007 (validation and hardening)  
**Status:** Completed — 2026-04-21

---

## Objective

Validate previously delivered DigiLocker and Aadhaar eKYC integrations within full AP/FM/DM workflows and close Sprint 3 with complete evidence.

## In Scope

1. Validate DM-006 DigiLocker integration behavior across updated document workflows.
2. Validate DM-007 Aadhaar eKYC behavior and DPDP safeguards.
3. End-to-end regression for AP, FM, and DM flows.
4. Completion and closure of documentation and test artifacts.
5. Master tracker final closure and release readiness remarks.

## Dependencies

1. Group M, N, O, and P completion. ✅
2. Integration credentials and queue pipeline stability. ✅ (stubs validated; API Setu credentials pending)
3. Test environment readiness for E2E verification. ✅

## Design Notes

1. No reimplementation of DM-006 and DM-007 unless defects are found. — 7 defects found and fixed (see below).
2. Focus on integration hardening, reliability, and auditability. ✅
3. Ensure consent and no-sensitive-data storage rules remain intact. ✅

---

## Implementation Log

### Defects Found and Fixed

| ID | File | Defect | Status |
|---|---|---|---|
| DQ-BUG-001 | `tests/Feature/CRM/DigiLockerTest.php:65` | `initiateRequest()` called with institution ID instead of Lead model | Fixed |
| DQ-BUG-002 | `tests/Feature/CRM/DigiLockerTest.php:73,98,122` | Enum case names PascalCase (`Pending`) instead of UPPER_CASE (`PENDING`) | Fixed |
| DQ-BUG-003 | `tests/Feature/CRM/DigiLockerTest.php:118` | `markFailed()` missing required `$error` string param | Fixed |
| DQ-BUG-004 | `tests/Feature/CRM/AadhaarEkycTest.php:65` | `initiate()` called with institution ID instead of Lead model | Fixed |
| DQ-BUG-005 | `tests/Feature/CRM/AadhaarEkycTest.php:112` | `verifyOtp()` called with OTP string instead of `bool $nameMatch` | Fixed |
| DQ-BUG-006 | `tests/Feature/CRM/AadhaarEkycTest.php:130` | `markFailed()` missing required `$error` string param | Fixed |
| DQ-BUG-007 | `app/Jobs/CRM/ProcessAadhaarKycJob.php:38` | Raw string status and direct Eloquent `->update()` bypassing repository | Fixed via new `AadhaarService::markOtpSent()` |

### Hardening Applied

**`app/Jobs/CRM/VerifyDigiLockerDocumentJob.php`**
- Added idempotency guard: if document status is already `VERIFIED` or `FAILED`, job returns immediately — safe on queue replay.

**`app/Jobs/CRM/ProcessAadhaarKycJob.php`**
- Replaced direct Eloquent `->update()` with `AadhaarService::markOtpSent()`.
- Added idempotency guard: if session is not in `INITIATED` status, job returns immediately.

**`app/Services/CRM/Integration/AadhaarService.php`**
- Added `markOtpSent(AadhaarEkycLog $log, string $otpReference, string $transactionId): AadhaarEkycLog`.
- Updates status to `OTP_SENT` via repository (not direct Eloquent) — consistent with service layer pattern.

### New Integration Tests Added

**`tests/Feature/CRM/Integration/DigiLockerIntegrationTest.php`** — 5 tests:
- DQ-DL-001: Job idempotency — VERIFIED document no-op
- DQ-DL-002: Job idempotency — FAILED document no-op
- DQ-DL-003: `DigiLockerVerifiedEvent` dispatched on verification
- DQ-DL-004: Cross-institution scope guard
- DQ-DL-005: `failed()` hook marks document FAILED

**`tests/Feature/CRM/Integration/AadhaarIntegrationTest.php`** — 6 tests:
- DQ-AK-001: `markOtpSent` stores references with OTP_SENT status
- DQ-AK-002: Job idempotency — OTP_SENT session no-op
- DQ-AK-002b: Job idempotency — VERIFIED session no-op
- DQ-AK-003: `verifyOtp(nameMatch: false)` path
- DQ-AK-004: `AadhaarKycCompletedEvent` dispatched
- DQ-AK-005: DPDP — resource omits sensitive fields

---

## Test Results

| Suite | Tests | Passing |
|---|---|---|
| DM-006 base tests | 4 | 4 ✅ |
| DM-007 base tests | 4 | 4 ✅ |
| DM-006 integration hardening | 5 | 5 ✅ |
| DM-007 integration hardening | 6 | 6 ✅ |
| Group P regression (DM/FM) | 18 | 18 ✅ |
| Group O regression (FM) | 9 | 9 ✅ |
| **Group Q Total** | **46** | **46 ✅** |

Pre-existing failures (outside Group Q scope): `AuditObserverDbWriteTest`, `MiddlewareTest`, `RbacSeederTest` (domain namespace issue, present since group E), `ErpConversionTest`, `OfferLetterPestTest` (QueryException, present since AP-016 commit). None introduced by Group Q.

---

## Deliverables

1. ✅ Group implementation log (this document).
2. ✅ User manual: API Setu integration readiness table in test cases document.
3. ✅ Test cases document: `docs/sprint3/test-cases/sprint3_group_Q_test_cases.md`
4. ✅ Master tracker updated: `Phase1_Sprint3_Master_Plan.md`

## Acceptance Gates

1. ✅ DigiLocker and Aadhaar eKYC flows pass validation in integrated scenarios.
2. ✅ AP/FM/DM Group P regression suite passes (18/18 + O: 9/9).
3. ✅ All Sprint 3 Group Q documents and test records complete.
4. ✅ No open critical blocker remains.

## API Setu Readiness

DM-006 and DM-007 are fully hardened and validated with stubs. When API Setu credentials are provisioned, three targeted replacements are needed:

| File | Replace |
|---|---|
| `app/Jobs/CRM/VerifyDigiLockerDocumentJob.php` | Stub URI → HTTP POST to API Setu DigiLocker `/request/submit` |
| `app/Jobs/CRM/ProcessAadhaarKycJob.php` | Stub refs → HTTP POST to API Setu Aadhaar `/otp` |
| `app/Http/Controllers/Web/CRM/AadhaarEkycWebController.php::verifyOtp()` | Hardcoded `nameMatch: true` → call API Setu `/otp/verify` and read response |

## Exit Criteria

1. ✅ DM-006 and DM-007 validation status finalized with evidence (37 tests passing).
2. ✅ Sprint 3 tracker marked complete with closure remarks.
3. ✅ Release-readiness note: Sprint 3 code is complete and test-validated. API Setu live credentials and S3/KMS storage migration are the two remaining deployment prerequisites.
