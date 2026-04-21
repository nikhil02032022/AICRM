# Group O — Payment Gateway Integration Handover

**Status:** Code scaffolding complete; **production gateway credentials and full PayU/CCAvenue adapter implementations are still pending.** Razorpay adapter is functionally complete against the public REST contract but has been tested only with `Http::fake()` — no live transaction has been attempted.

This document is the to-do list and reference for the engineer who will finish the gateway integration.

---

## 1. Current State

| Gateway | Adapter file | State |
|---|---|---|
| Razorpay | [RazorpayGateway.php](../../app/Services/CRM/Payments/Gateways/RazorpayGateway.php) | Full impl (createOrder, verifySignature, parseWebhook, fetchStatus, initiateRefund). Needs live-credential validation. |
| PayU | [PayUGateway.php](../../app/Services/CRM/Payments/Gateways/PayUGateway.php) | **Stub** — every method throws `RuntimeException`. |
| CCAvenue | [CCAvenueGateway.php](../../app/Services/CRM/Payments/Gateways/CCAvenueGateway.php) | **Stub** — every method throws `RuntimeException`. |

Adapter contract: [PaymentGatewayInterface.php](../../app/Services/CRM/Payments/Gateways/PaymentGatewayInterface.php). Factory: [PaymentGatewayManager.php](../../app/Services/CRM/Payments/Gateways/PaymentGatewayManager.php).

Gateway driver is selected per-transaction via the `gateway` column on `payment_transactions`, defaulting to `config('crm_payments.default_gateway')`.

---

## 2. Dummy Credentials Currently in Use

`config/crm_payments.php` reads from env. `.env.example` ships with placeholder values that **MUST** be replaced before any non-test environment is used:

```
RAZORPAY_KEY_ID=rzp_test_DUMMY_KEY_ID
RAZORPAY_KEY_SECRET=DUMMY_KEY_SECRET_REPLACE_ME
RAZORPAY_WEBHOOK_SECRET=DUMMY_WEBHOOK_SECRET_REPLACE_ME
PAYU_MERCHANT_KEY=DUMMY_PAYU_KEY
PAYU_MERCHANT_SALT=DUMMY_PAYU_SALT
PAYU_WEBHOOK_SECRET=DUMMY_PAYU_WEBHOOK_SECRET
CCAVENUE_MERCHANT_ID=DUMMY_CCA_MID
CCAVENUE_ACCESS_CODE=DUMMY_CCA_ACCESS_CODE
CCAVENUE_WORKING_KEY=DUMMY_CCA_WORKING_KEY
```

For multi-tenant deployments these belong in `integration_credentials` per institution (already used for ERP — see `IntegrationCredential::getCredential()`). Plan to migrate gateway secrets there when an institution onboards.

---

## 3. To-Do — Razorpay (Promote from sandbox to production)

1. **Obtain credentials** from finance/ops:
   - Live `key_id`, `key_secret` (Razorpay dashboard → Settings → API Keys).
   - **Webhook secret** (dashboard → Settings → Webhooks → Create webhook). Endpoint to register: `https://<host>/api/v1/crm/payments/webhooks/razorpay`. Subscribe to events: `payment.captured`, `payment.failed`, `order.paid`, `refund.processed`.
2. **Smoke test** with a ₹1 test transaction:
   - Initiate via `FeeCollectionService::initiate()` → check `payment_transactions.gateway_order_id` populated.
   - Pay via Razorpay test card `4111 1111 1111 1111`, any future expiry, OTP `1234`.
   - Confirm webhook hit; transaction status flips to `success`; `PaymentConfirmed` listener fires.
3. **Refund smoke test** through `RefundService::financeApprove()` → `ProcessGatewayRefundJob`.
4. **Add a fetchStatus reconciliation job** (not yet built) — daily Artisan command that pulls open transactions older than 24h and calls `RazorpayGateway::fetchStatus()` to detect missed webhooks. Suggested file: `app/Console/Commands/CRM/ReconcileOpenPaymentsCommand.php`.

---

## 4. To-Do — PayU (Implement Adapter)

PayU's hosted-checkout flow differs from Razorpay's order API:

| Step | What to add |
|---|---|
| `createOrder()` | PayU does not have a server-side "create order" call. Build the form payload (`txnid`, `amount`, `productinfo`, `firstname`, `email`, `phone`, `surl`, `furl`, `hash`) and return it in `GatewayOrder::checkoutPayload`. The `hash` is `sha512(key|txnid|amount|productinfo|firstname|email|||||||||salt)`. |
| `verifySignature()` | PayU posts a response hash on the success/failure URL: `sha512(salt|status|||||||||||email|firstname|productinfo|amount|txnid|key)`. Compare in reverse-key order. |
| `parseWebhook()` | PayU sends form-encoded POST with fields: `mihpayid`, `status`, `txnid`, `amount`, `error_Message`. Map `status='success'` → `PaymentStatus::SUCCESS`, `status='failure'` → `FAILED`. |
| `fetchStatus()` | POST to `${base_url}/merchant/postservice.php?form=2` with `key`, `command=verify_payment`, `var1=<txnid>`, `hash`. |
| `initiateRefund()` | Same endpoint, `command=cancel_refund_transaction`, `var1=<mihpayid>`, `var2=<token>`, `var3=<amount>`. |

PayU PHP SDK: `payu/payu-php-sdk` (composer) — consider using it instead of hand-rolling. If you do, keep all SDK calls behind the adapter so the rest of the system stays gateway-agnostic.

Test card (test mode): `5123 4567 8901 2346`, expiry `05/26`, CVV `123`, OTP `123456`.

---

## 5. To-Do — CCAvenue (Implement Adapter)

CCAvenue uses **AES-128-CBC encryption** on the entire request payload (not just signing):

| Step | What to add |
|---|---|
| `createOrder()` | Build query string `merchant_id=...&order_id=<txn.idempotency_key>&currency=INR&amount=...&redirect_url=...&cancel_url=...&billing_email=...`. Encrypt with `working_key` (MD5 → key, hex-decoded), then base64. Return checkout URL `${base_url}/transaction/transaction.do?command=initiateTransaction&merchant_id=<mid>&encRequest=<encrypted>&access_code=<code>`. |
| `verifySignature()` | CCAvenue does not sign — instead the response is encrypted with the same working key. Treat "decrypt successfully" as the verification. |
| `parseWebhook()` | Decrypt `encResp` POST field, parse query string. Map `order_status='Success'` → SUCCESS, `Failure`/`Aborted` → FAILED. |
| `fetchStatus()` | POST to `${base_url}/apis/servlet/DoWebTrans` with `command=orderStatusTracker`, `request_type=JSON`. |
| `initiateRefund()` | Same endpoint, `command=refundOrder`. |

Reference helper for AES-128-CBC encryption (CCAvenue spec):
```php
$key = md5($workingKey, true);
$iv  = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f";
$cipher = openssl_encrypt($plain, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
return bin2hex($cipher);
```

CCAvenue does not provide public sandbox without onboarding — coordinate with Ops to get a test merchant_id before implementation.

---

## 6. Webhook Endpoint Reference

A single endpoint resolves the adapter from the URL parameter:

```
POST /api/v1/crm/payments/webhooks/{gateway}
```

`{gateway}` ∈ `razorpay | payu | ccavenue`. Controller: [WebhookController.php](../../app/Http/Controllers/CRM/Api/Payments/WebhookController.php).

The controller:
1. Resolves the adapter via `PaymentGatewayManager::driver()`.
2. Calls `verifySignature($rawBody, $headers)` — invalid → HTTP 401 + audit row in `payment_webhook_events` with `signature_valid=false`.
3. Calls `parseWebhook($payload)` to get a `NormalizedEvent`.
4. Hands off to `PaymentWebhookService::handle()` which is **idempotent** on `(gateway, event_id)`.

When you implement PayU/CCAvenue webhook URLs, register them in the respective dashboards exactly as above.

---

## 7. Configuration Knobs

[config/crm_payments.php](../../config/crm_payments.php):

- `default_gateway` — fallback when transaction doesn't specify.
- `link.ttl_minutes` — payment link expiry (default 3 days).
- `reminders.cadence_days` — array of offsets relative to due date; default `[-3, -1, +1]`.
- `webhook.tolerance_seconds` — for replay protection if you add timestamp checks.
- `redact_keys` — payload keys stripped before persistence in `raw_request`/`raw_response`. Add gateway-specific keys here as needed (e.g., `card_token`, `vpa_handle`).

---

## 8. Security & Compliance Checklist

- [ ] Real credentials stored in **vault / encrypted IntegrationCredential**, not `.env` in the repo.
- [ ] Webhook URL is HTTPS-only and behind a CDN/WAF; throttle middleware (`throttle:120,1`) is already applied.
- [ ] Add request IP allow-listing for each gateway (Razorpay publishes IP ranges).
- [ ] PCI-DSS scope: we never persist PAN/CVV — but verify `PayloadRedactor::redact()` covers all gateway-specific sensitive keys before merging new adapters.
- [ ] DPDP: ensure the `raw_request` / `raw_response` JSON columns never receive applicant PII beyond what's already in CRM.
- [ ] Run `payment_webhook_events` retention policy (suggested 18 months) — add a pruner.

---

## 9. Open Items / Known Gaps

1. **Reconciliation job** — daily/hourly poll for stuck `pending` transactions older than N hours (calls `fetchStatus`). Not yet built.
2. **Currency support** — code paths assume INR; multi-currency requires fee_structure currency to flow into checkout payload (already does for Razorpay) plus exchange rate handling for ERP.
3. **Saved instruments** (Razorpay tokens for repeat tuition payments) — out of scope for Group O; revisit in tuition installment work (Group P, FM-009).
4. **Gateway selection per-institution** — currently global default. When multiple institutions go live, expose a per-institution preference (probably `institution_settings.preferred_gateway`).
5. **PayU + CCAvenue** — adapters throw `RuntimeException`. Calling `FeeCollectionService::initiate(..., GatewayProvider::PAYU)` or `::CCAVENUE` will fail. Ensure UI hides those options until implementation is in.
6. **Production webhook secret rotation** — write a runbook for rotating without dropping events (use overlapping secrets briefly).

---

## 10. Where to Start

For the next engineer, in order:

1. Read [PaymentGatewayInterface.php](../../app/Services/CRM/Payments/Gateways/PaymentGatewayInterface.php) and the Razorpay impl as a reference.
2. Provision Razorpay live credentials, run a ₹1 end-to-end smoke test.
3. Build the reconciliation Artisan command.
4. Implement PayU adapter (most-used Indian gateway after Razorpay).
5. Implement CCAvenue adapter once a sandbox merchant is provisioned.
6. After each adapter: add a feature test mirroring `tests/Feature/CRM/Payments/RazorpayWebhookTest.php` against `Http::fake()`.

Document any new fields, env vars, or dashboard config back into this file as it changes.
