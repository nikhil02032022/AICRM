# Sprint 4 - Group S: Student Applicant Portal and Self-Service

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** S
**Module:** Student Applicant Portal and Self-Service
**Req IDs:** CRM-SP-001 to CRM-SP-008
**Status:** ✅ Completed (2026-04-21)
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

- [x] OTP tokens are hashed before storage; never logged in plain text.
- [x] Portal session tokens are hashed; short-lived (configurable, default 8h).
- [x] Applicant can only download their own documents (policy check on `Application.applicant_id`).
- [x] ERP bridge token is single-use and expires in 5 minutes.
- [x] DPDP: right-to-access is surfaced from portal (CR-004 from Group W uses same portal infrastructure).
- [x] Rate limiting on OTP requests (max 5 per 10 minutes per mobile/email).
- [x] Domain-based branding resolution does not allow cross-institution data leakage.

---

## Implementation Log

### SP-001 — Branded, Mobile-Responsive Portal per Institution ✅
**Date:** 2026-04-21 | **Status:** Completed

**Files Created:**
- `config/crm_portal.php` — portal config (OTP expiry, session lifetime, ERP bridge URL, branding defaults)
- `app/Http/Middleware/CRM/Portal/BrandingMiddleware.php` — resolves institution from domain or `?institution={uuid}` bypass; shares `$branding` and `$institution` with all portal views
- `resources/views/components/layouts/portal-guest.blade.php` — unauthenticated branded portal shell (`<x-layouts.portal-guest>`)
- `resources/views/components/layouts/portal-app.blade.php` — authenticated portal shell with sidebar nav (`<x-layouts.portal-app>`)
- `resources/views/portal/layouts/_nav.blade.php` — sidebar navigation partial (shared between mobile + desktop)
- `tests/Feature/CRM/Portal/PortalBrandingTest.php` — 7 Pest tests, all passing

**Files Modified:**
- `bootstrap/app.php` — registered `portal.branding` middleware alias
- `routes/web.php` — created new `/portal` route group with `portal.branding` + `throttle:60,1`; moved existing `portal/offers` routes inside it
- `resources/views/portal/offers/show.blade.php` — migrated from inline HTML to `<x-layouts.portal-guest>` component

**Notes:**
- SP-001 and SP-008 (institutional branding) share the same implementation — both will be marked complete together
- The `portal/layouts/app.blade.php` and `portal/layouts/guest.blade.php` files remain in `resources/views/portal/layouts/` as organisational placeholders; the actual Blade components are in `resources/views/components/layouts/`
- Production domain routing requires DNS wildcard or per-institution domain config (DevOps task)
- The `?institution={uuid}` query param provides a development bypass when domain-based routing is not configured

**Test Results:** 7/7 passing (`tests/Feature/CRM/Portal/PortalBrandingTest.php`)

---

### SP-002 — OTP Authentication (Email) ✅
**Date:** 2026-04-21 | **Status:** Completed

**Scope change:** SMS OTP deferred; Sprint 4 delivers **email-only OTP** as per updated requirement. Channel column retained in the database for future SMS support.

**Files Created:**
- `database/migrations/2026_04_21_110000_create_portal_otp_tokens_table.php` — lead_uuid, institution_id, channel, token_hash (SHA-256), expires_at, used_at, ip_address
- `database/migrations/2026_04_21_110001_create_portal_sessions_table.php` — lead_uuid, institution_id, session_token_hash (SHA-256), expires_at, device_fingerprint
- `app/Models/CRM/Portal/PortalOtpToken.php` — isValid(), isExpired(), isUsed(), markUsed()
- `app/Models/CRM/Portal/PortalSession.php` — isExpired(), lead() BelongsTo
- `app/Services/CRM/Portal/OtpService.php` — isRateLimited(), sendOtp(), verify(); uses RateLimiter facade keyed on `portal_otp:{institution_id}:{lead_uuid}`
- `app/Services/CRM/Portal/PortalAuthService.php` — issueSession(), validateSession(), revokeSession()
- `app/Mail/CRM/Portal/PortalOtpMail.php` — delivers 6-digit code; subject: "Your login code for {institution_name}"
- `app/Http/Middleware/CRM/Portal/PortalAuthenticate.php` — validates `portal_session` cookie; sets `portal_session` request attribute and `$portalSession` view variable
- `app/Http/Controllers/CRM/Portal/PortalAuthController.php` — showLogin, sendOtp, showVerifyOtp, verifyOtp, logout
- `resources/views/portal/auth/login.blade.php` — email entry form; uses `<x-layouts.portal-guest>`
- `resources/views/portal/auth/verify-otp.blade.php` — 6-digit code entry; autocomplete="one-time-code"
- `resources/views/mail/portal/otp.blade.php` — branded HTML email template
- `tests/Feature/CRM/Portal/PortalOtpAuthTest.php` — 16 Pest tests

**Files Modified:**
- `bootstrap/app.php` — registered `portal.auth` middleware alias (`PortalAuthenticate`)
- `routes/web.php` — added SP-002 auth routes under `/portal/auth` prefix: login (GET/POST), verify (GET/POST), logout (POST)

**Route names registered:**
| Route | Name |
|-------|------|
| GET /portal/auth/login | `portal.auth.login` |
| POST /portal/auth/login | `portal.auth.send-otp` |
| GET /portal/auth/verify | `portal.auth.verify-otp` |
| POST /portal/auth/verify | `portal.auth.do-verify` |
| POST /portal/auth/logout | `portal.auth.logout` |

**Security controls implemented:**
- OTP: 6-digit code, SHA-256 hashed before storage, expires in 10 min (configurable), single-use
- Rate limiting: 5 OTP requests per 10-minute window per applicant via Laravel RateLimiter
- Session token: 64-char random string, SHA-256 hashed, expires in 8h (configurable), HttpOnly + Secure + SameSite=Lax cookie
- Login vagueness: unregistered emails silently redirect — no enumeration possible
- Cross-institution isolation: Lead lookup always scoped by `institution_id`

**Test Results:** 16 tests in `tests/Feature/CRM/Portal/PortalOtpAuthTest.php`

---

### SP-003 — Dashboard: Application Status, Documents, Payments, Appointments ✅
**Date:** 2026-04-21 | **Status:** Completed

**Files Created:**
- `app/Services/CRM/Portal/PortalDashboardService.php` — aggregates applications (with programme, offer letter, payments, documents), calculates pending mandatory document count per application, fetches upcoming counselling sessions
- `app/Http/Controllers/CRM/Portal/PortalDashboardController.php` — resolves lead from portal session; passes aggregated data to view
- `resources/views/portal/dashboard.blade.php` — responsive dashboard: per-application status cards (programme name, status badge, document checklist summary, payment total, offer letter indicator) + upcoming appointments panel + empty state
- `tests/Feature/CRM/Portal/PortalDashboardTest.php` — 12 Pest feature tests

**Files Modified:**
- `routes/web.php` — added `GET /portal` (redirect → `portal.dashboard`) and `GET /portal/dashboard` (`portal.dashboard`) under `portal.auth` middleware group
- `routes/web.php` — added `use PortalDashboardController` import
- `resources/views/components/layouts/portal-app.blade.php` — fixed logout route from `portal.logout` (wrong) to `portal.auth.logout`

**Routes registered:**

| Route | Name |
|-------|------|
| GET /portal | `portal.home` (redirects to dashboard) |
| GET /portal/dashboard | `portal.dashboard` |

**Architecture notes:**
- `PortalDashboardService::getData()` uses `withoutGlobalScopes()` to bypass `InstitutionScope` (same pattern as SP-002 auth); institution isolation is enforced explicitly via `where('institution_id', $institution->id)`.
- Document checklist completeness: looks up the active `DocumentChecklist` for the application's `programme_id`, counts mandatory items not yet represented in `ApplicationDocument` rows for that application.
- Payments aggregation: only `PaymentStatus::SUCCESS` transactions are counted towards the confirmed total.
- Upcoming appointments: `CounsellingSession` rows for the lead where `scheduled_at > now()` and status is not terminal (not cancelled, completed, or no_show), ordered ascending, capped at 5.
- Logout button in `portal-app` layout was silently hidden due to wrong route name (`portal.logout` vs `portal.auth.logout`) — fixed as part of this sprint item.

**Test Results:** 12 tests in `tests/Feature/CRM/Portal/PortalDashboardTest.php`

---

### SP-005 — Downloadable Offer Letter, Admission Confirmation, Payment Receipts ✅
**Date:** 2026-04-21 | **Status:** Completed

**Files Created:**
- `app/Services/CRM/Portal/PortalDownloadService.php` — ownership-gated PDF service with three methods:
  - `offerLetterUrl(Lead, $appUuid, Institution)` → returns 15-min signed S3 temporary URL for the stored offer letter PDF
  - `admissionLetterPdf(Lead, $appUuid, Institution)` → renders admission confirmation PDF on-the-fly using `spipu/html2pdf`; only available after offer acceptance
  - `paymentReceiptPdf(Lead, $txnUuid, Institution)` → renders payment receipt PDF on-the-fly; only for `PaymentStatus::SUCCESS` transactions
- `app/Http/Controllers/CRM/Portal/PortalDownloadController.php` — three download endpoints; uses `portal_session` and `portal_institution` request attributes set by `PortalAuthenticate` middleware
- `tests/Feature/CRM/Portal/PortalDownloadTest.php` — 11 Pest feature tests

**Files Modified:**
- `routes/web.php` — added `use PortalDownloadController` import; added SP-005 download route group under `/portal/downloads` prefix with `portal.auth` middleware

**Routes registered:**

| Route | Name |
|-------|------|
| GET /portal/downloads/{applicationUuid}/offer-letter | `portal.downloads.offer-letter` |
| GET /portal/downloads/{applicationUuid}/admission-letter | `portal.downloads.admission-letter` |
| GET /portal/downloads/receipts/{transactionUuid} | `portal.downloads.payment-receipt` |

**Architecture notes:**
- Offer letter PDF: served via `Storage::disk('s3')->temporaryUrl()` (15-min expiry); PDF is generated asynchronously by `GenerateOfferLetterJob` and path stored on `OfferLetter::pdf_path`. Returns error flash if PDF not yet generated.
- Admission letter: generated inline using `spipu/html2pdf`; requires `offer_letter.status = accepted` and `acceptance_recorded_at` set.
- Payment receipt: generated inline using `spipu/html2pdf`; requires `payment_transaction.status = PaymentStatus::SUCCESS`.
- All three endpoints validate that the application/transaction belongs to the authenticated lead AND institution — no cross-applicant data access possible.
- Error handling: `RuntimeException` from the service is caught and flashed as a session error; `AuthorizationException` (wrong applicant) results in the same redirect-back-with-error pattern.

**Test Results:** 11 tests in `tests/Feature/CRM/Portal/PortalDownloadTest.php`

---

### SP-006 — Multiple Simultaneous Applications ✅
**Date:** 2026-04-21 | **Status:** Completed

**Files Created:**
- `app/Services/CRM/Portal/PortalApplicationService.php` — two public methods:
  - `list(Lead, Institution)` → returns collection of enriched application summary arrays (same structure as `PortalDashboardService::buildApplicationData`)
  - `detail(string $uuid, Lead, Institution)` → returns full detail array including `history` (status history), `checklist` (DocumentChecklist with items), and `uploaded_ids` (submitted item IDs); throws `AuthorizationException` if the UUID belongs to a different lead or institution
- `app/Http/Controllers/CRM/Portal/PortalApplicationController.php` — `index()` lists all applications; `show($applicationUuid)` renders detail view; catches `AuthorizationException` and redirects to index with error flash
- `resources/views/portal/applications/index.blade.php` — card list with programme name, submission date, status badge, document pending count, payment total quick-stat, offer-letter indicator, "View details" link to show route
- `resources/views/portal/applications/show.blade.php` — 3-column responsive detail: header card (programme + status badge), status history timeline (left), document checklist with required/optional split and per-item submitted/pending state (left), offer-letter section with download link + admission letter link when accepted (right), per-transaction payment history list with receipt download links (right)
- `tests/Feature/CRM/Portal/PortalMultiApplicationTest.php` — 11 Pest feature tests

**Files Modified:**
- `routes/web.php` — added `use PortalApplicationController` import; added SP-006 route group under `portal.auth` middleware
- `docs/sprint4/sprint4_group_S_student_portal.md` — added this implementation log entry

**Routes registered:**

| Route | Name |
|-------|------|
| GET /portal/applications | `portal.applications.index` |
| GET /portal/applications/{applicationUuid} | `portal.applications.show` |

**Architecture notes:**
- The `My Applications` sidebar nav item (already declared in `_nav.blade.php` as `portal.applications.index`) is now live — the route exists so the item renders as a clickable link rather than disabled.
- `PortalApplicationService::detail()` checks `$app->lead_uuid !== $lead->uuid` after scoping by `institution_id`; this double-check ensures neither a cross-lead nor a cross-institution URL is served.
- Status history is eager-loaded and ordered `created_at` ascending so the timeline reads chronologically.
- Document checklist split (required vs optional) is computed from `is_mandatory` on `DocumentChecklistItem` in the view layer; no service change required.
- Payment receipt download links in the detail view reuse the existing `portal.downloads.payment-receipt` route (SP-005).
- Admission letter download link is shown only when `currentOfferLetter->status === 'accepted'`, matching the SP-005 service guard.

**Test Results:** 11 tests in `tests/Feature/CRM/Portal/PortalMultiApplicationTest.php`

---

### SP-007 — ERP Bridge Token on Enrolment ✅
**Date:** 2026-04-21 | **Status:** Completed

**Files Created:**
- `database/migrations/2026_04_21_110002_create_erp_bridge_tokens_table.php` — `lead_uuid`, `institution_id`, `application_uuid`, `token_hash`, `issued_at`, `expires_at`, `used_at`
- `app/Models/CRM/Portal/ErpBridgeToken.php` — `isExpired()`, `isUsed()`, `isValid()`, `markUsed()` helpers; same pattern as `PortalOtpToken`
- `app/Services/CRM/Portal/ErpBridgeService.php` — `issue()`, `buildRedirectUrl()`, `consume()`, `isEnabled()` (feature flag)
- `app/Http/Controllers/CRM/Portal/PortalErpBridgeController.php` — validates session + application ownership; stubs gracefully when bridge disabled
- `tests/Unit/CRM/Portal/ErpBridgeServiceTest.php` — 12 Pest unit tests
- `tests/Feature/CRM/Portal/ErpBridgeTest.php` — 9 Pest feature tests

**Files Modified:**
- `routes/web.php` — added `use PortalErpBridgeController`; registered `GET /portal/applications/{applicationUuid}/erp-transition` (`portal.applications.erp-transition`) under `portal.auth` middleware
- `resources/views/portal/applications/show.blade.php` — added "Student Portal Access" card with "Go to Student Portal" button; card is rendered only when `$application->status->value === 'enrolled'`

**Routes registered:**

| Route | Name |
|-------|------|
| GET /portal/applications/{applicationUuid}/erp-transition | `portal.applications.erp-transition` |

**Architecture notes:**
- Token generation: `bin2hex(random_bytes(40))` → 80-char hex plain token; SHA-256 hash stored in DB. Plain token is never persisted.
- Token is single-use and expires in `erp_bridge_token_expiry_minutes` minutes (default 5, configurable via `ERP_BRIDGE_TOKEN_EXPIRY_MINUTES` env).
- Redirect URL format: `{erp_bridge_base_url}/sso?token={plain}&institution={uuid}&applicant={uuid}`. ERP portal is expected to call a consume endpoint in Sprint 5.
- Feature flag: when `ERP_BRIDGE_BASE_URL` is empty (default), `ErpBridgeService::isEnabled()` returns `false`; the controller redirects back with an info flash and no token row is created — satisfying the Sprint 4 mitigation strategy.
- Authorization: double-checks `application.lead_uuid === authenticated lead uuid` AND `application.institution_id === resolved institution.id` before issuing — same pattern as `PortalApplicationService::detail()`.
- The `consume()` method is fully implemented for Sprint 5 ERP-side use; it validates not-used + not-expired + correct institution, marks the token used, and returns the record.
- `withoutGlobalScopes()` used throughout to bypass `InstitutionScope`, with explicit institution scoping (same pattern as SP-002 through SP-006).

**Test Results:** 12 unit tests in `tests/Unit/CRM/Portal/ErpBridgeServiceTest.php` | 9 feature tests in `tests/Feature/CRM/Portal/ErpBridgeTest.php`

---

### SP-004 — Applicant Chat with Assigned Counsellor ✅
**Date:** 2026-04-21 | **Status:** Completed

**Files Created:**
- `database/migrations/2026_04_21_110003_create_portal_messages_table.php` — `lead_uuid`, `institution_id`, `direction` (INBOUND/OUTBOUND), `body` (text, encrypted), `sent_by_user_id` (nullable, counsellor), `applicant_read_at` (nullable)
- `app/Models/CRM/Portal/PortalMessage.php` — `isFromApplicant()`, `isFromCounsellor()`, `isReadByApplicant()` helpers; body encrypted via `'encrypted'` cast
- `app/Services/CRM/Portal/PortalChatService.php` — `getThread()`, `sendFromApplicant()`, `markOutboundRead()`, `unreadOutboundCount()`
- `app/Http/Controllers/CRM/Portal/PortalChatController.php` — `index()` (loads thread, marks outbound read), `store()` (validates + sends)
- `resources/views/portal/chat/index.blade.php` — chat bubble UI; applicant messages right-aligned (primary colour), counsellor messages left-aligned (gray); compose textarea with character limit; auto-scrolls to bottom on load
- `tests/Feature/CRM/Portal/PortalChatTest.php` — 12 Pest feature tests

**Files Modified:**
- `app/Enums/CRM/CommunicationChannel.php` — added `PORTAL = 'PORTAL'` case with label (`Portal Chat`) and icon
- `routes/web.php` — added `use PortalChatController`; registered `GET /portal/chat` (`portal.chat.index`) and `POST /portal/chat` (`portal.chat.store`) under `portal.auth` middleware

**Routes registered:**

| Route | Name |
|-------|------|
| GET /portal/chat | `portal.chat.index` |
| POST /portal/chat | `portal.chat.store` |

**Architecture notes:**
- CC-021 bridge: every applicant `sendFromApplicant()` call also creates a `CommunicationLog` row (`channel = PORTAL`, `direction = INBOUND`, `loggable` → `PortalMessage`) so the counsellor sees the message in their CRM unified inbox alongside email/SMS/WhatsApp.
- `body_preview` in `CommunicationLog` is `mb_substr(strip_tags($body), 0, 150)` — no PII in the log preview, consistent with CC-021 design.
- Counsellor-side reply (posting `PortalMessage` OUTBOUND rows) is not implemented in this sprint — messages are written directly by a counsellor CRM action in a future sprint (SP-004 extension). The portal view already handles OUTBOUND rendering and `markOutboundRead()`.
- `portal_messages.body` uses Laravel's `'encrypted'` cast — encrypted/decrypted transparently via `APP_KEY`; same standard as `WhatsAppMessage.body`.
- Cross-applicant isolation: `getThread()` and `markOutboundRead()` always filter by both `lead_uuid` AND `institution_id` — no cross-lead data access possible.
- The `Chat` nav item in `_nav.blade.php` was already declared (`portal.chat.index`); registering the route makes it active automatically (Route::has check in nav template).

**Test Results:** 12 tests in `tests/Feature/CRM/Portal/PortalChatTest.php`

---

### SP-008 — Institutional Branding (Logo, Colours, Domain) ✅
**Date:** 2026-04-21 | **Status:** Completed

Implemented jointly with SP-001. See SP-001 implementation log above for full details.

- `BrandingMiddleware` resolves institution from domain/subdomain or `?institution={uuid}` dev bypass
- Branding config (`logo_url`, `primary_colour`, `secondary_colour`, `institution_name`) injected into all portal views via `$branding` view variable
- Portal layouts (`portal-guest.blade.php`, `portal-app.blade.php`) consume `$branding` for header logo and CSS custom properties
- `config/crm_portal.php` defines branding defaults

**Test Results:** Covered by 7 tests in `tests/Feature/CRM/Portal/PortalBrandingTest.php`

---

## Group S Completion Summary

**Completed:** 2026-04-21
**Total tests:** ~90 Pest tests (unit + feature) across all SP items
**Req IDs closed:** SP-001, SP-002, SP-003, SP-004, SP-005, SP-006, SP-007, SP-008

| Req ID | Title | Tests | Status |
|--------|-------|-------|--------|
| SP-001 | Branded mobile-responsive portal | 7 (PortalBrandingTest) | ✅ |
| SP-002 | OTP authentication (email) | 16 (PortalOtpAuthTest) | ✅ |
| SP-003 | Dashboard (status, documents, payments, appointments) | 12 (PortalDashboardTest) | ✅ |
| SP-004 | Applicant chat with counsellor | 12 (PortalChatTest) | ✅ |
| SP-005 | Downloadable offer letter, admission letter, receipts | 11 (PortalDownloadTest) | ✅ |
| SP-006 | Multiple simultaneous applications | 11 (PortalMultiApplicationTest) | ✅ |
| SP-007 | ERP bridge token on enrolment | 21 (ErpBridgeServiceTest + ErpBridgeTest) | ✅ |
| SP-008 | Institutional branding | covered by SP-001 | ✅ |

**Open items / deferred:**
- SMS OTP channel deferred to Sprint 5 (channel column retained in DB for future use)
- Counsellor-side reply from CRM inbox (SP-004 extension) deferred to future sprint
- ERP bridge consume endpoint (Sprint 5 EI module)
