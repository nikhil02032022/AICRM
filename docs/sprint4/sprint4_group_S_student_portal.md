# Sprint 4 - Group S: Student Applicant Portal and Self-Service

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** S
**Module:** Student Applicant Portal and Self-Service
**Req IDs:** CRM-SP-001 to CRM-SP-008
**Status:** Pending
**Dependencies:** AP (Sprint 3 Groups M/N), FM (Sprint 3 Groups O/P), DM (Sprint 3 Groups P/Q), CC-021 Unified Inbox (Sprint 1 Group F), AP-012 Offer Letter PDF (Sprint 3 Group N)

---

## Objective

Deliver a branded, mobile-responsive self-service portal for applicants to track their application journey, download documents, communicate with their counsellor, and transition seamlessly to the ERP student portal on enrolment.

## In Scope

1. Branded, mobile-responsive applicant portal configurable per institution.
2. OTP-based authentication (mobile and/or email).
3. Portal dashboard showing application status, document checklist, payment history, and upcoming appointments.
4. Applicant chat with assigned counsellor from within the portal.
5. Downloadable offer letter, admission confirmation letter, and payment receipts.
6. Support for multiple simultaneous applications (student applying to multiple programmes).
7. Seamless ERP portal transition on enrolment (same credentials, no re-registration).
8. Institutional branding (logo, colours, domain).

## Out of Scope

- Full LMS/ERP portal features (managed by A2A ERP — SP-007 bridge only).
- Native mobile portal app (deferred to Sprint 5 MB module).
- Walk-in token queue (EC-019 — Should Have, not in Sprint 4).

## Dependencies

1. `Application` model and status states from Sprint 3 Group M.
2. Offer letter PDF generator from Sprint 3 Group N (`OfferLetterGenerator`).
3. `Payment` and `PaymentReceipt` models from Sprint 3 Group O.
4. `DocumentChecklist` and `ApplicantDocument` models from Sprint 3 Group P.
5. Appointment model from Sprint 1 Group E (EC-015 to EC-017).
6. Unified communication inbox (CC-021) from Sprint 1 Group F for counsellor chat.
7. Institution branding config (may partially overlap with SA-006 from Group W).

## Design Notes

1. Portal runs on a separate route prefix (`/portal`) with its own guest-layout Blade.
2. OTP authentication uses a stateless session token after verification; standard Laravel session for subsequent requests.
3. Branding is resolved from `InstitutionBrandingConfig` per domain or subdomain via `BrandingMiddleware`.
4. Portal chat reuses the CC-021 unified inbox data layer; counsellor sees the chat in their CRM inbox.
5. SP-007 ERP bridge issues a one-time signed token to the ERP portal on enrolment conversion event; no password is stored or shared.
6. All portal downloads are gated behind the authenticated applicant's own records only.

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for applicant portal usage.
3. Group S test cases document (`test-cases/sprint4_group_S_test_cases.md`).
4. Master tracker status and remarks update.

## Acceptance Gates

1. Applicant can register and authenticate via OTP on mobile and email.
2. Dashboard correctly displays application status per programme, pending documents, payment history, and next appointment.
3. Chat widget sends/receives messages that appear in counsellor's CRM inbox.
4. Offer letter, admission letter, and payment receipt download generates correct PDF for the authenticated applicant only.
5. Multiple programme applications all appear on the same applicant dashboard.
6. On enrolment conversion, a single-click ERP transition link is issued with a signed token (no password re-entry).
7. Portal renders institution logo, colours, and domain correctly.
8. No cross-applicant data access possible.

## Risks and Mitigation

1. OTP delivery failures (SMS gateway issues):
   Mitigation: support both mobile OTP and email OTP as fallback; rate-limit OTP requests.
2. ERP token bridge requiring ERP API readiness:
   Mitigation: stub the bridge service with a feature flag; ERP integration completed in Sprint 5.

## Exit Criteria

1. SP-001 to SP-008 marked completed in master tracker.
2. ~20 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. QA sign-off recorded.

---

## File Manifest

### Migrations
- `create_portal_otp_tokens_table.php` — applicant_id, channel (sms/email), token_hash, expires_at, used_at, ip_address
- `create_portal_sessions_table.php` — applicant_id, session_token_hash, expires_at, device_fingerprint
- `create_erp_bridge_tokens_table.php` — applicant_id, token_hash, issued_at, used_at, expires_at

### Models
- `App\Models\CRM\Portal\PortalOtpToken`
- `App\Models\CRM\Portal\PortalSession`
- `App\Models\CRM\Portal\ErpBridgeToken`

### Services
- `App\Services\CRM\Portal\OtpService` — generate, send (SMS/email), verify, expire
- `App\Services\CRM\Portal\PortalAuthService` — issue session token, validate, logout
- `App\Services\CRM\Portal\PortalDashboardService` — aggregate application status, documents, payments, appointments for applicant
- `App\Services\CRM\Portal\PortalDownloadService` — fetch and stream offer letter / confirmation / receipt PDFs
- `App\Services\CRM\Portal\ErpBridgeService` — generate signed one-time token for ERP portal handoff (stubbed until Sprint 5 EI)
- `App\Services\CRM\Portal\PortalChatService` — proxy to CC-021 UnifiedInbox for applicant-side chat

### Controllers (Portal — separate route group `/portal`)
- `App\Http\Controllers\CRM\Portal\PortalAuthController` — showLogin, sendOtp, verifyOtp, logout
- `App\Http\Controllers\CRM\Portal\PortalDashboardController` — index
- `App\Http\Controllers\CRM\Portal\PortalApplicationController` — index (list all applications), show
- `App\Http\Controllers\CRM\Portal\PortalDocumentController` — index (checklist), upload
- `App\Http\Controllers\CRM\Portal\PortalPaymentController` — index (payment history)
- `App\Http\Controllers\CRM\Portal\PortalDownloadController` — offerLetter, admissionLetter, paymentReceipt
- `App\Http\Controllers\CRM\Portal\PortalChatController` — index, store (send message)
- `App\Http\Controllers\CRM\Portal\PortalErpBridgeController` — redirect (issue signed token and redirect to ERP)
- `App\Http\Controllers\CRM\Portal\PortalAppointmentController` — index

### Controllers (API)
- `App\Http\Controllers\Api\V1\CRM\Portal\PortalOtpApiController` — requestOtp, verifyOtp

### Middleware
- `App\Http\Middleware\CRM\Portal\BrandingMiddleware` — resolves institution from domain/subdomain, injects branding into view
- `App\Http\Middleware\CRM\Portal\PortalAuthenticate` — validates portal session token

### Livewire Components
- `App\Livewire\CRM\Portal\PortalDashboard` — reactive dashboard panel
- `App\Livewire\CRM\Portal\PortalChat` — real-time chat with counsellor

### Views (Blade — `/portal` layout)
- `resources/views/portal/layouts/app.blade.php` — branded portal shell
- `resources/views/portal/auth/login.blade.php`
- `resources/views/portal/auth/verify-otp.blade.php`
- `resources/views/portal/dashboard.blade.php`
- `resources/views/portal/applications/index.blade.php`
- `resources/views/portal/applications/show.blade.php`
- `resources/views/portal/documents/index.blade.php`
- `resources/views/portal/payments/index.blade.php`
- `resources/views/portal/appointments/index.blade.php`
- `resources/views/portal/chat/index.blade.php`
- `resources/views/portal/downloads/offer-letter.blade.php`

### Config
- `config/crm_portal.php` — otp_expiry_minutes, session_lifetime_hours, branding defaults, erp_bridge_base_url

### Seeders
- `Database\Seeders\CRM\Portal\PortalTestApplicantSeeder`

### Tests
- `tests/Unit/CRM/Portal/OtpServiceTest.php`
- `tests/Unit/CRM/Portal/PortalAuthServiceTest.php`
- `tests/Unit/CRM/Portal/ErpBridgeServiceTest.php`
- `tests/Feature/CRM/Portal/PortalOtpAuthTest.php`
- `tests/Feature/CRM/Portal/PortalDashboardTest.php`
- `tests/Feature/CRM/Portal/PortalDownloadTest.php`
- `tests/Feature/CRM/Portal/PortalChatTest.php`
- `tests/Feature/CRM/Portal/PortalBrandingTest.php`
- `tests/Feature/CRM/Portal/PortalMultiApplicationTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| SP-001 | Branded mobile-responsive portal per institution | `BrandingMiddleware`, portal layout, `InstitutionBrandingConfig` |
| SP-002 | OTP authentication (mobile + email) | `OtpService`, `PortalAuthController`, `PortalAuthenticate` middleware |
| SP-003 | Dashboard: status, documents, payments, appointments | `PortalDashboardService`, `PortalDashboard` Livewire |
| SP-004 | Applicant chat with counsellor | `PortalChatService`, `PortalChat` Livewire, CC-021 inbox bridge |
| SP-005 | Downloadable offer letter, confirmation, receipts | `PortalDownloadService`, `PortalDownloadController` |
| SP-006 | Multiple simultaneous applications | `PortalApplicationController::index()`, reuses Sprint 3 `Application` model |
| SP-007 | ERP portal transition (same credentials, no re-registration) | `ErpBridgeService`, `PortalErpBridgeController`, `ErpBridgeToken` model |
| SP-008 | Institutional branding (logo, colours, domain) | `BrandingMiddleware`, `config/crm_portal.php`, portal layout |

---

## Security Checklist

- [ ] OTP tokens are hashed before storage; never logged in plain text.
- [ ] Portal session tokens are hashed; short-lived (configurable, default 8h).
- [ ] Applicant can only download their own documents (policy check on `Application.applicant_id`).
- [ ] ERP bridge token is single-use and expires in 5 minutes.
- [ ] DPDP: right-to-access is surfaced from portal (CR-004 from Group W uses same portal infrastructure).
- [ ] Rate limiting on OTP requests (max 5 per 10 minutes per mobile/email).
- [ ] Domain-based branding resolution does not allow cross-institution data leakage.

---

## Implementation Log

*(To be updated as implementation progresses)*
