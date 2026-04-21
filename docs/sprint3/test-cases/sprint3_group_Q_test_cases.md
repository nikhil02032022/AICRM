# Sprint 3 — Group Q Test Cases
## Document Integrations Validation and Sprint Closure

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** Q  
**Req IDs:** CRM-DM-006 (DigiLocker), CRM-DM-007 (Aadhaar eKYC)  
**Date:** 2026-04-21  
**Status:** Completed

---

## Summary

| Category | Total | Automated | Manual | Passing | Failing |
|---|---|---|---|---|---|
| DM-006 DigiLocker (base) | 4 | 4 | 0 | 4 | 0 |
| DM-006 DigiLocker (integration hardening) | 5 | 5 | 0 | 5 | 0 |
| DM-007 Aadhaar eKYC (base) | 4 | 4 | 0 | 4 | 0 |
| DM-007 Aadhaar eKYC (integration hardening) | 6 | 6 | 0 | 6 | 0 |
| Regression (Group P DM/FM) | 18 | 18 | 0 | 18 | 0 |
| **Total Group Q** | **37** | **37** | **0** | **37** | **0** |

---

## Defects Found and Fixed

| ID | File | Defect | Fix Applied |
|---|---|---|---|
| DQ-BUG-001 | DigiLockerTest.php:65 | `initiateRequest` called with institution ID instead of Lead model | Corrected to pass `$lead` object |
| DQ-BUG-002 | DigiLockerTest.php:73,98,122 | Enum case names used PascalCase (`Pending`, `Verified`, `Failed`) not matching actual UPPER_CASE enum | Fixed to `PENDING`, `VERIFIED`, `FAILED` |
| DQ-BUG-003 | DigiLockerTest.php:118 | `markFailed()` called without required `$error` string param | Added `'Simulated failure'` argument |
| DQ-BUG-004 | AadhaarEkycTest.php:65 | `initiate()` called with institution ID instead of Lead model | Corrected to pass `$lead` object + `consentIp` string |
| DQ-BUG-005 | AadhaarEkycTest.php:112 | `verifyOtp()` called with OTP string `'123456'` — service expects `bool $nameMatch` | Changed to `true` |
| DQ-BUG-006 | AadhaarEkycTest.php:130 | `markFailed()` called without required `$error` string param | Added `'Simulated failure'` argument |
| DQ-BUG-007 | ProcessAadhaarKycJob.php:38 | Raw string `'otp_sent'` used for status; direct Eloquent `->update()` bypassing repository | Replaced with `$service->markOtpSent()` via new service method; added idempotency guard |

---

## Hardening Applied

| Item | Description |
|---|---|
| `VerifyDigiLockerDocumentJob` idempotency | Added guard: if document is already `VERIFIED` or `FAILED`, job returns immediately — no-op on replay |
| `ProcessAadhaarKycJob` idempotency | Added guard: if session is not `INITIATED`, job returns immediately — safe on replay |
| `AadhaarService::markOtpSent()` | New method: updates status to `OTP_SENT` and stores `otp_reference` + `transaction_id` via repository (event-safe, not direct Eloquent update) |

---

## DM-006 DigiLocker — Base Test Cases

### TC-DQ-DM006-001
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM006-001 |
| **Req ID** | CRM-DM-006 |
| **Description** | initiateRequest creates DigiLockerDocument and dispatches VerifyDigiLockerDocumentJob |
| **Preconditions** | Institution, lead, and consent record exist |
| **Steps** | Call `DigiLockerService::initiateRequest($lead, 'marksheet_10', 1)` |
| **Expected Result** | `DigiLockerDocument` created with status `REQUESTED`; `VerifyDigiLockerDocumentJob` pushed to queue |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/DigiLockerTest.php` |

### TC-DQ-DM006-002
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM006-002 |
| **Req ID** | CRM-DM-006 |
| **Description** | markVerified sets document to VERIFIED with URI and storage path |
| **Preconditions** | DigiLockerDocument in REQUESTED status |
| **Steps** | Call `markVerified($document, $uri, $storagePath)` |
| **Expected Result** | Status = `VERIFIED`, `is_verified = true`, `verified_at` set |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/DigiLockerTest.php` |

### TC-DQ-DM006-003
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM006-003 |
| **Req ID** | CRM-DM-006 |
| **Description** | markFailed sets document status to FAILED |
| **Preconditions** | DigiLockerDocument in REQUESTED status |
| **Steps** | Call `markFailed($document, 'Simulated failure')` |
| **Expected Result** | Status = `FAILED` |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/DigiLockerTest.php` |

### TC-DQ-DM006-004
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM006-004 |
| **Req ID** | CRM-DM-006 |
| **Description** | DigiLockerDocument list is scoped to institution |
| **Preconditions** | Two institutions; one document each |
| **Steps** | Call `list($institutionId)` for institution A |
| **Expected Result** | Returns only institution A's document (total = 1) |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/DigiLockerTest.php` |

---

## DM-006 DigiLocker — Integration Hardening Test Cases

### TC-DQ-DL-001
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DL-001 |
| **Req ID** | CRM-DM-006 |
| **Description** | Job idempotency — re-run on VERIFIED document is a no-op |
| **Preconditions** | DigiLockerDocument in VERIFIED status |
| **Steps** | Run `VerifyDigiLockerDocumentJob::handle()` directly |
| **Expected Result** | Document status unchanged; `DigiLockerVerifiedEvent` NOT dispatched |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/DigiLockerIntegrationTest.php` |

### TC-DQ-DL-002
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DL-002 |
| **Req ID** | CRM-DM-006 |
| **Description** | Job idempotency — re-run on FAILED document is a no-op |
| **Preconditions** | DigiLockerDocument in FAILED status |
| **Steps** | Run `VerifyDigiLockerDocumentJob::handle()` directly |
| **Expected Result** | Document status unchanged; no event dispatched |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/DigiLockerIntegrationTest.php` |

### TC-DQ-DL-003
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DL-003 |
| **Req ID** | CRM-DM-006 |
| **Description** | DigiLockerVerifiedEvent dispatched on successful verification |
| **Preconditions** | DigiLockerDocument in REQUESTED status |
| **Steps** | Run `VerifyDigiLockerDocumentJob::handle()` |
| **Expected Result** | Status = VERIFIED; `DigiLockerVerifiedEvent` dispatched with correct document ID |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/DigiLockerIntegrationTest.php` |

### TC-DQ-DL-004
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DL-004 |
| **Req ID** | CRM-DM-006 |
| **Description** | initiateRequest scopes document to the lead's institution |
| **Preconditions** | Two institutions; lead belongs to institution A |
| **Steps** | Call `initiateRequest($lead, ...)` then `list($institutionB->id)` |
| **Expected Result** | Document `institution_id` = lead's institution; institution B list = empty |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/DigiLockerIntegrationTest.php` |

### TC-DQ-DL-005
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DL-005 |
| **Req ID** | CRM-DM-006 |
| **Description** | failed() hook marks document FAILED after max retries |
| **Preconditions** | DigiLockerDocument in REQUESTED status |
| **Steps** | Call `VerifyDigiLockerDocumentJob::failed(new RuntimeException(...))` |
| **Expected Result** | Status = FAILED; error_message = 'Job failed after max retries' |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/DigiLockerIntegrationTest.php` |

---

## DM-007 Aadhaar eKYC — Base Test Cases

### TC-DQ-DM007-001
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM007-001 |
| **Req ID** | CRM-DM-007 |
| **Description** | initiate creates AadhaarEkycLog and dispatches ProcessAadhaarKycJob |
| **Preconditions** | Institution and lead exist |
| **Steps** | Call `AadhaarService::initiate($lead, '127.0.0.1')` |
| **Expected Result** | `AadhaarEkycLog` created with status `INITIATED`; `ProcessAadhaarKycJob` pushed |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/AadhaarEkycTest.php` |

### TC-DQ-DM007-002
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM007-002 |
| **Req ID** | CRM-DM-007 / DPDP |
| **Description** | AadhaarEkycLog table has no aadhaar_number column (DPDP compliance) |
| **Preconditions** | AadhaarEkycLog created |
| **Steps** | Inspect `$log->toArray()` |
| **Expected Result** | No `aadhaar_number` key present |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/AadhaarEkycTest.php` |

### TC-DQ-DM007-003
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM007-003 |
| **Req ID** | CRM-DM-007 |
| **Description** | verifyOtp marks log VERIFIED with kyc_complete=true |
| **Preconditions** | AadhaarEkycLog in OTP_SENT status |
| **Steps** | Call `verifyOtp($log, true)` |
| **Expected Result** | Status = VERIFIED; kyc_complete = true |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/AadhaarEkycTest.php` |

### TC-DQ-DM007-004
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-DM007-004 |
| **Req ID** | CRM-DM-007 |
| **Description** | markFailed sets status to FAILED |
| **Preconditions** | AadhaarEkycLog in OTP_SENT status |
| **Steps** | Call `markFailed($log, 'Simulated failure')` |
| **Expected Result** | Status = FAILED |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/AadhaarEkycTest.php` |

---

## DM-007 Aadhaar eKYC — Integration Hardening Test Cases

### TC-DQ-AK-001
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-AK-001 |
| **Req ID** | CRM-DM-007 |
| **Description** | markOtpSent stores otp_reference and transaction_id with status OTP_SENT |
| **Preconditions** | AadhaarEkycLog in INITIATED status |
| **Steps** | Call `markOtpSent($log, 'OTP-REF', 'TXN-001')` |
| **Expected Result** | Status = OTP_SENT; otp_reference and transaction_id stored |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/AadhaarIntegrationTest.php` |

### TC-DQ-AK-002
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-AK-002 |
| **Req ID** | CRM-DM-007 |
| **Description** | Job idempotency — re-run on OTP_SENT session is a no-op |
| **Preconditions** | AadhaarEkycLog in OTP_SENT status with existing references |
| **Steps** | Run `ProcessAadhaarKycJob::handle()` directly |
| **Expected Result** | otp_reference unchanged; status remains OTP_SENT |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/AadhaarIntegrationTest.php` |

### TC-DQ-AK-002b
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-AK-002b |
| **Req ID** | CRM-DM-007 |
| **Description** | Job idempotency — re-run on VERIFIED session is a no-op |
| **Preconditions** | AadhaarEkycLog in VERIFIED status |
| **Steps** | Run `ProcessAadhaarKycJob::handle()` directly |
| **Expected Result** | Status remains VERIFIED |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/AadhaarIntegrationTest.php` |

### TC-DQ-AK-003
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-AK-003 |
| **Req ID** | CRM-DM-007 |
| **Description** | verifyOtp with nameMatch=false sets kyc_complete=true, name_match=false |
| **Preconditions** | AadhaarEkycLog in OTP_SENT status |
| **Steps** | Call `verifyOtp($log, false)` |
| **Expected Result** | Status = VERIFIED; kyc_complete = true; name_match = false |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/AadhaarIntegrationTest.php` |

### TC-DQ-AK-004
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-AK-004 |
| **Req ID** | CRM-DM-007 |
| **Description** | AadhaarKycCompletedEvent dispatched on verifyOtp success |
| **Preconditions** | AadhaarEkycLog in OTP_SENT status |
| **Steps** | Call `verifyOtp($log, true)` |
| **Expected Result** | `AadhaarKycCompletedEvent` dispatched with correct ekycLog ID |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/AadhaarIntegrationTest.php` |

### TC-DQ-AK-005
| Field | Value |
|---|---|
| **Scenario ID** | TC-DQ-AK-005 |
| **Req ID** | CRM-DM-007 / DPDP |
| **Description** | AadhaarEkycLogResource omits sensitive fields (DPDP compliance) |
| **Preconditions** | AadhaarEkycLog with otp_reference and transaction_id stored |
| **Steps** | Render `AadhaarEkycLogResource::toArray()` |
| **Expected Result** | Response has no `otp_reference`, `transaction_id`, `aadhaar_number`, or `consent_ip` keys |
| **Actual Result** | ✅ Pass |
| **Automated** | Yes — `tests/Feature/CRM/Integration/AadhaarIntegrationTest.php` |

---

## Regression Results (Groups M–P)

Regression run date: 2026-04-21

| Group | Scope | Tests | Result |
|---|---|---|---|
| P — Document Core | DM-001 to DM-010 (Documents: upload, approve, reject, encryption, completeness) | 8 | ✅ All passing |
| P — Scholarship Core | FM-006 to FM-009 (eligibility, approval lifecycle, installments) | 13 | ✅ All passing |
| O — Fee & Payments | FM-001 to FM-005, FM-010 to FM-013 (initiation, webhook, refund, ERP migration, installment) | 9 | ✅ All passing |
| Unit — Documents | DM-008 encryption, DM-010 completeness | 5 | ✅ All passing |
| Unit — Scholarships | FM-007 eligibility evaluator | 5 | ✅ All passing |
| Unit — Payments | FM-013 payload redactor | 1 | ✅ All passing |

**Pre-existing failures (not Group Q):** `AuditObserverDbWriteTest` (8), `MiddlewareTest` (1), `RbacSeederTest` (1) — all reference `App\Domain\CRM\*` namespace classes not present in this codebase (present since group E, commit ffb3613). `ErpConversionTest` and `OfferLetterPestTest` — `QueryException` failures present since Group N commit bb315f1 (AP-016). None introduced by Group Q.

---

## DPDP Safeguards Verification

| Control | Verified By |
|---|---|
| Aadhaar number never stored in DB | TC-DQ-DM007-002 (schema assertion) |
| API response omits otp_reference, transaction_id | TC-DQ-AK-005 (resource inspection) |
| Consent IP and timestamp captured at initiation | AadhaarEkycLog migration: `consent_ip`, `consent_at` columns |
| No Aadhaar number in error logs | `ProcessAadhaarKycJob::failed()` logs only 'Job failed after max retries' |

---

## API Setu Integration Readiness

Both DM-006 and DM-007 are validated with stubs. When API Setu credentials are provisioned:

| Action | File | What to Replace |
|---|---|---|
| DigiLocker document pull | `app/Jobs/CRM/VerifyDigiLockerDocumentJob.php` | Stub URI generation → HTTP POST to API Setu `/digilocker/request/submit`; retrieve actual URI and download |
| Aadhaar OTP send | `app/Jobs/CRM/ProcessAadhaarKycJob.php` | Stub refs → HTTP POST to API Setu `/aadhaar/otp`; store returned `transaction_id` and `otp_reference` |
| Aadhaar OTP verify | `app/Http/Controllers/Web/CRM/AadhaarEkycWebController.php::verifyOtp()` | Hardcoded `nameMatch: true` → call API Setu `/aadhaar/otp/verify`; read `name_match` from response |
