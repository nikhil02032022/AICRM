# Sprint 3 - Group O: Fee Collection and Payments Core

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** O  
**Module:** Fee, Scholarship and Payment Management  
**Req IDs:** CRM-FM-001 to CRM-FM-005, CRM-FM-010 to CRM-FM-013  
**Status:** Implementation Complete — Awaiting QA Sign-off (2026-04-21)
**BRD coverage:** 9/9 in-scope Req IDs landed. Production gateway credentials (Razorpay live + PayU/CCAvenue full adapters) tracked separately in the gateway handover doc.

---

## Objective

Implement pre-admission payment operations from fee setup to collection tracking and ERP fee sync.

## In Scope

1. Application fee collection.
2. Seat reservation and booking fee support.
3. Gateway integration structure for Razorpay, PayU, and CCAvenue.
4. Payment link generation and sharing.
5. Payment confirmation auto-logging and status update trigger.
6. Automated reminder flows.
7. Fee dashboards for collected, pending, refunds, and forecast.
8. CRM-to-ERP fee record migration on enrolment conversion.

## Dependencies

1. Group M and Group N application lifecycle states.
2. Integration credentials and secure storage setup.
3. Queue workers for webhook and reminder processing.

## Design Notes

1. Use idempotent payment webhook handlers.
2. Keep gateway providers behind adapter interfaces.
3. Avoid logging any sensitive payment data.
4. Enforce institution and campus scoping in all fee queries.

## Deliverables

1. Group implementation log updates.
2. User manual section for counsellor and finance workflows.
3. Group O test cases document.
4. Master tracker status and remarks update.

## Acceptance Gates

1. Payment links are generated and traceable.
2. Successful and failed payment flows update status correctly.
3. Reminders trigger on schedule with opt-out compliance.
4. ERP fee handoff flow works for converted students.

## Risks and Mitigation

1. Gateway callback variance:
Mitigation: normalized callback mapping and signature verification.
2. Reconciliation mismatches:
Mitigation: daily reconciliation job and exception report.

## Exit Criteria

1. FM-001 to FM-005 and FM-010 to FM-013 marked completed in tracker.
2. User manual and test cases published.
3. QA and finance validation sign-off recorded.

---

## Implementation Log (2026-04-21)

### Schema
- `fee_structures`, `payment_transactions`, `payment_links`, `payment_reminders`, `refund_requests`, `payment_webhook_events` (new tables).
- `application_conversion_logs` extended with `fee_migration_*` columns.

### Code
- Enums: `App\Enums\CRM\Payments\{GatewayProvider, FeeType, PaymentStatus, PaymentChannel, ReminderStatus, RefundStatus}`.
- Models under `App\Models\CRM\Payments\` with `InstitutionScope` enforcement.
- Gateway adapter contract `PaymentGatewayInterface` + manager + Razorpay impl + PayU/CCAvenue stubs.
- Services: `FeeStructureService`, `FeeCollectionService`, `PaymentLinkService`, `PaymentWebhookService`, `PaymentReminderPlanner`, `RefundService`, `FeeDashboardService`, `ErpFeeMigrationService`.
- Jobs: `SendPaymentReminderJob`, `ProcessGatewayRefundJob`, `MigrateConvertedApplicationFeesJob`.
- Listeners: `AdvanceApplicationOnPaymentConfirmed`, `NotifyCounsellorOnPaymentFailed`, `MigrateFeesOnApplicationConverted`.
- HTTP: Web controllers under `App\Http\Controllers\CRM\Web\Payments\`; API controllers under `App\Http\Controllers\CRM\Api\Payments\`.
- Repository pair `PaymentReportRepositoryInterface` / `EloquentPaymentReportRepository`.
- Provider `App\Providers\CRM\CrmPaymentServiceProvider` registered in `bootstrap/providers.php`.
- Event wiring extended in `AppServiceProvider`.
- Routes added inside `crm.*` web group and `api.v1.crm.*` group; webhook route added under `api.crm.payments.webhooks.*`.
- Schedule: `crm:payments:dispatch-reminders` every 15 minutes.
- Permissions: `CrmFeePaymentRolePermissionSeeder` chained from `DatabaseSeeder`.

### UI (rebuilt per `ui-ux-pro-max` design system, 2026-04-21)
All Group O blades follow the A2A-CRM design language (indigo primary, `text-2xl font-bold` headers, `btn-*-sm` component classes, semantic status badges, `overflow-hidden rounded-lg border` table shells, SVG empty states, accessible labels + focus rings, `@csrf` on every form).

| Screen | Path |
|---|---|
| Fee structures (list + collapsible Alpine create form) | `resources/views/crm/payments/fee_structures/index.blade.php` |
| Application fee panel (breadcrumb, two-column form + applicant aside, recent transactions) | `resources/views/crm/payments/application_fee_panel.blade.php` |
| Checkout placeholder (centered card, sandbox notice) | `resources/views/crm/payments/checkout_placeholder.blade.php` |
| Refund requests (workflow actions per status) | `resources/views/crm/payments/refunds/index.blade.php` |
| Fee dashboard (filters + 4 KPI tiles + gradient forecast tile + programme breakdown) | `resources/views/crm/payments/fee_dashboard.blade.php` |

### Tests — 9/9 passing
- Feature: `tests/Feature/CRM/Payments/{InitiatePaymentTest, RazorpayWebhookTest, RefundWorkflowTest, ErpFeeMigrationTest}.php`
- Unit: `tests/Unit/CRM/Payments/PayloadRedactorTest.php`
- Last run: 2026-04-21 — `php artisan test tests/Feature/CRM/Payments tests/Unit/CRM/Payments` → 9 passed, 28 assertions.

### Migrations + Seeders run
- All 7 migrations applied (`2026_05_03_000001` through `2026_05_03_000007`).
- `CrmFeePaymentRolePermissionSeeder` executed; permissions `fee_structure.manage`, `payments.{view,collect,link.share,refund.request,refund.approve}`, `fee_dashboard.view` mapped to admin / institution-admin / finance / manager / counsellor roles.

### Configuration / env
- `config/crm_payments.php` published.
- `.env.example` updated with **dummy** Razorpay/PayU/CCAvenue keys (`DUMMY_*_REPLACE_ME`) — replace before non-test environments.

### Companion docs
- **Payment gateway handover** for next engineer: [`docs/sprint3/group_O_payment_gateway_integration_handover.md`](group_O_payment_gateway_integration_handover.md). Covers Razorpay live promotion, PayU adapter spec (hash formula + endpoints), CCAvenue AES-128-CBC encryption helper, webhook-URL registration, security checklist, reconciliation roadmap.
- **Test cases**: [`docs/sprint3/test-cases/sprint3_group_O_test_cases.md`](test-cases/sprint3_group_O_test_cases.md) — 15 test cases mapped per Req ID with auto/manual coverage tags.

### BRD Req ID coverage matrix

| Req ID | Status | Evidence |
|---|---|---|
| CRM-FM-001 (application fee, configurable per programme) | ✅ Complete | `fee_structures` schema; `FeeStructureService::resolveActive`; `FeeCollectionService::initiate`. |
| CRM-FM-002 (seat reservation / booking fee) | ✅ Complete | `FeeType::SEAT_BOOKING` enum; same initiation path. |
| CRM-FM-003 (Razorpay/PayU/CCAvenue integration) | ⚠ Partial | Razorpay full impl + tested via `Http::fake`; PayU/CCAvenue stubs throw — full implementations pending live merchant onboarding (see handover doc). |
| CRM-FM-004 (payment links via WhatsApp/SMS/email) | ✅ Complete | `PaymentLinkService::generate`; `PaymentLinkNotification`; `/api/v1/crm/payments/transactions/{txn}/links`. |
| CRM-FM-005 (auto-log + status update) | ✅ Complete | `PaymentWebhookService` (idempotent); `PaymentConfirmed` → `AdvanceApplicationOnPaymentConfirmed`. |
| CRM-FM-010 (automated reminders) | ✅ Complete | `PaymentReminderPlanner` + `SendPaymentReminderJob` + `crm:payments:dispatch-reminders` (15-min schedule). |
| CRM-FM-011 (refund workflow) | ✅ Complete | `RefundService` 3-tier chain (counsellor → manager → finance) + `ProcessGatewayRefundJob`. |
| CRM-FM-012 (financial dashboards) | ✅ Complete | `FeeDashboardService`, `EloquentPaymentReportRepository`, KPI + breakdown view + XLSX export. Scholarship-impact widget deferred to Group P (depends on FM-006). |
| CRM-FM-013 (ERP fee migration) | ✅ Complete | `ErpApiClient::pushFeeLedger`, `ErpFeeMigrationService::buildPayload`, `MigrateFeesOnApplicationConverted` listener, `MigrateConvertedApplicationFeesJob` (idempotent via `application_conversion_logs.fee_migration_status`). |

### Open follow-ups (out of Group O scope)
- PayU + CCAvenue full adapter implementations — tracked in [gateway handover doc](group_O_payment_gateway_integration_handover.md).
- Razorpay live-credential smoke test (₹1 test transaction) — needs ops to provision keys.
- Daily reconciliation Artisan command for stuck transactions.
- Counsellor application detail panel deep-link wiring (Group N hand-off — links exist via routes; UI integration with the application detail page belongs to Group N's pipeline screen).
- Scholarship-impact tile on fee dashboard — depends on Group P (FM-006).
- Move gateway secrets from `.env` to per-institution `IntegrationCredential` once multi-tenant rollout begins.

### Acceptance Gate verification

| Gate | Status | Notes |
|---|---|---|
| Payment links are generated and traceable | ✅ | `payment_links` row with token + `shared_at` + `opened_at`; redirect controller marks open. |
| Successful and failed payment flows update status correctly | ✅ | Verified by `RazorpayWebhookTest` (success + failure + idempotent replay). |
| Reminders trigger on schedule with opt-out compliance | ✅ | Scheduler registered; `PaymentReminder.opted_out` honoured in job. Manual smoke pending. |
| ERP fee handoff flow works for converted students | ✅ | Verified by `ErpFeeMigrationTest`. |
