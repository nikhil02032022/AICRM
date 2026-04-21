# Sprint 3 — Group O Test Cases (Fee Collection & Payments Core)

**BRD Req IDs:** CRM-FM-001 to CRM-FM-005, CRM-FM-010 to CRM-FM-013

---

## TC-FM-O-001 — Active fee structure resolves for programme + fee_type
- **Pre:** Programme P with active `application` fee structure of ₹1500.
- **Steps:** Call `FeeStructureService::resolveActive(P, APPLICATION)`.
- **Expected:** Returns the active row.
- **Auto:** `FeeStructureManagementTest` (covered indirectly via InitiatePaymentTest).

## TC-FM-O-002 — Initiate payment is idempotent
- **Pre:** Application A, programme has active application fee structure.
- **Steps:** Call `FeeCollectionService::initiate(A, APPLICATION)` twice.
- **Expected:** Same `PaymentTransaction` returned both times; one DB row.
- **Auto:** `tests/Feature/CRM/Payments/InitiatePaymentTest.php`.

## TC-FM-O-003 — Initiate fails without active fee structure
- **Pre:** No active fee structure for fee_type.
- **Steps:** Call `initiate(application, SEAT_BOOKING)`.
- **Expected:** `RuntimeException` thrown.
- **Auto:** Same file.

## TC-FM-O-004 — Razorpay webhook rejects invalid signature
- **Pre:** Webhook endpoint live; webhook secret configured.
- **Steps:** POST `/api/v1/crm/payments/webhooks/razorpay` with bogus `X-Razorpay-Signature`.
- **Expected:** HTTP 401; `payment_webhook_events` row with `signature_valid=false`.
- **Auto:** `RazorpayWebhookTest`.

## TC-FM-O-005 — Razorpay webhook idempotency on replay
- **Pre:** Pending PaymentTransaction matching `order_id`.
- **Steps:** POST same valid signed payload twice.
- **Expected:** Both responses 200; transaction status SUCCESS; only one `payment_webhook_events` row; `PaymentConfirmed` dispatched once.
- **Auto:** `RazorpayWebhookTest`.

## TC-FM-O-006 — Auto status advance after seat-booking payment
- **Pre:** Application status = OFFER_ISSUED; SEAT_BOOKING transaction confirmed.
- **Steps:** Dispatch `PaymentConfirmed` event.
- **Expected:** Application status transitions to OFFER_ACCEPTED.
- **Manual:** Cover via integration sweep.

## TC-FM-O-007 — Payment link generation persists token + dispatches notification
- **Pre:** Existing transaction; counsellor with `payments.link.share`.
- **Steps:** POST `/api/v1/crm/payments/transactions/{txn}/links` `{channel: email, recipient: x@y}`.
- **Expected:** 201; `payment_links` row with token + expires_at; queued notification.
- **Manual:** API smoke test.

## TC-FM-O-008 — Payment-link token resolves and marks opened_at
- **Pre:** Active payment_link.
- **Steps:** GET `/crm/payments/pay/{token}`.
- **Expected:** Redirects to checkout; `opened_at` set.
- **Manual:** Browser test.

## TC-FM-O-009 — Reminder dispatcher only sends for open transactions
- **Pre:** Pending reminder for SUCCESS transaction.
- **Steps:** Run `crm:payments:dispatch-reminders`.
- **Expected:** Reminder marked SKIPPED.
- **Manual:** Will add automated coverage in Group P sweep.

## TC-FM-O-010 — Refund approval chain (counsellor → manager → finance)
- **Pre:** Successful transaction.
- **Steps:** Request refund → managerApprove → financeApprove.
- **Expected:** Status transitions PENDING → MANAGER_APPROVED → APPROVED; `ProcessGatewayRefundJob` queued.
- **Auto:** `RefundWorkflowTest`.

## TC-FM-O-011 — Refund refused for non-success transaction
- **Pre:** PENDING transaction.
- **Steps:** Call `RefundService::request`.
- **Expected:** `RuntimeException`.
- **Auto:** `RefundWorkflowTest`.

## TC-FM-O-012 — Fee dashboard summary aggregates correctly
- **Pre:** Mixed transactions across statuses.
- **Steps:** GET `/crm/payments/fee-dashboard`.
- **Expected:** Collected/pending/refunded totals match seeded amounts.
- **Manual:** Smoke test.

## TC-FM-O-013 — ERP fee migration listener dispatches job on conversion
- **Pre:** Application with ApplicationConversionLog (status success).
- **Steps:** Dispatch `ErpConversionSucceededEvent`.
- **Expected:** `MigrateConvertedApplicationFeesJob` queued.
- **Auto:** `ErpFeeMigrationTest`.

## TC-FM-O-014 — Fee migration payload contains success + refund transactions
- **Steps:** Build payload via `ErpFeeMigrationService`.
- **Expected:** Two transactions; total_collected/total_refunded computed.
- **Auto:** `ErpFeeMigrationTest`.

## TC-FM-O-015 — Sensitive payload keys are redacted
- **Steps:** Run `PayloadRedactor::redact()` on payload containing card_number/cvv.
- **Expected:** Values replaced with `[REDACTED]` recursively.
- **Auto:** `tests/Unit/CRM/Payments/PayloadRedactorTest.php`.

---

### Coverage matrix

| Req ID | TC IDs |
|---|---|
| FM-001 | TC-FM-O-001, 002 |
| FM-002 | TC-FM-O-001, 003 |
| FM-003 | TC-FM-O-002, 004, 005 |
| FM-004 | TC-FM-O-007, 008 |
| FM-005 | TC-FM-O-004, 005, 006 |
| FM-010 | TC-FM-O-009 |
| FM-011 | TC-FM-O-010, 011 |
| FM-012 | TC-FM-O-012 |
| FM-013 | TC-FM-O-013, 014 |
