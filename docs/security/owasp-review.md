# A2A-CRM OWASP Top 10 Security Review

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Req ID:** NFR-SE-007  
**Review Date:** 2026-05-03  
**Reviewer:** Sprint 5 Group AC  
**Scope:** All Laravel application code as of Sprint 5 Group AC completion  

---

## Summary

| Category | Finding | Severity | Status |
|----------|---------|----------|--------|
| A01 — Broken Access Control | InstitutionScope global scope applied to all tenant models | None | PASS |
| A01 — Broken Access Control | UUID-based route model binding (not sequential IDs) | None | PASS |
| A01 — Broken Access Control | Gate/Policy enforcement on all controller actions | None | PASS |
| A02 — Cryptographic Failures | PII fields encrypted at rest (AES-256 via Laravel APP_KEY) | None | PASS |
| A02 — Cryptographic Failures | MFA secrets encrypted at rest (model cast: `encrypted`) | None | PASS |
| A02 — Cryptographic Failures | TLS in transit (nginx/infra responsibility, not Laravel code) | None | DOCUMENTED |
| A03 — Injection | All queries via Eloquent ORM; no unguarded raw queries found | None | PASS |
| A03 — Injection | Blade auto-escaping via `{{ }}` syntax — reviewed `{!! !!}` usages | Low | REVIEWED |
| A04 — Insecure Design | Multi-tenancy isolation via InstitutionScope on all models | None | PASS |
| A05 — Security Misconfiguration | Session SameSite upgraded to 'strict' (was 'lax') | Fixed | FIXED in AC |
| A05 — Security Misconfiguration | Session secure cookie defaults to true in production | Fixed | FIXED in AC |
| A06 — Vulnerable Components | Composer dependencies reviewed; no known CVEs as of 2026-05-03 | None | PASS |
| A07 — Identification/Auth Failures | TOTP MFA enforced for admin/manager roles | Fixed | FIXED in AC |
| A07 — Identification/Auth Failures | Session lifetime 120 minutes (configurable) | None | PASS |
| A07 — Identification/Auth Failures | Rate limiting on MFA routes (5 attempts/minute) | None | PASS |
| A08 — Software/Data Integrity | CSRF protection on all state-changing web routes | None | PASS |
| A08 — Software/Data Integrity | Webhook signature verification (HMAC-SHA256) | None | PASS |
| A09 — Logging/Monitoring | AuditObserver on Lead model | None | PASS |
| A09 — Logging/Monitoring | AiUsageLoggingService for all Claude API calls with PII redaction | None | PASS |
| A09 — Logging/Monitoring | Failed job monitoring via AlertFailedJobsJob | Fixed | FIXED in AC |
| A10 — Server-Side Request Forgery | No SSRF-susceptible code paths found | None | PASS |

---

## Detailed Findings

### A01 — Broken Access Control

**Finding: InstitutionScope global scope**  
All CRM models use `InstitutionScope` (auto-applied in `booted()`) which adds `WHERE institution_id = auth()->user()->institution_id` to every Eloquent query. Verified in: Lead, Application, CommunicationLog, Task, and 40+ other models.  
**Status: PASS**

**Finding: UUID route binding**  
All public-facing route model bindings use `{model:uuid}` (not sequential auto-increment IDs). Cross-tenant enumeration via ID guessing is prevented.  
**Status: PASS**

**Finding: Policy enforcement**  
Every controller action uses either `Gate::authorize()` or `$this->authorize()`. Verified by grep across all controllers — no unguarded public methods found.  
**Status: PASS**

---

### A02 — Cryptographic Failures

**Finding: PII encryption at rest**  
`Lead.mobile` and `Lead.email` use `'encrypted'` cast (AES-256-CBC via `APP_KEY`). MFA secret (`google2fa_secret`) and recovery codes (`mfa_recovery_codes`) also use encrypted casts. Never stored in plaintext.  
**Status: PASS**

**Finding: TLS in transit**  
Laravel configuration is TLS-agnostic (infra concern). Session `secure` cookie flag now defaults to true in production (fixed in AC). Deployment must run behind TLS-terminating proxy.  
**Status: DOCUMENTED** — Include TLS cert validity in deployment checklist.

---

### A03 — Injection

**Finding: ORM usage**  
All queries use Eloquent builder or `DB::table()` with parameterised bindings. `grep -r "DB::statement\|DB::unprepared\|whereRaw" app/` returned results only in migration files and one reporting query with safe escaping.  
**Status: PASS**

**Finding: Blade XSS — `{!! !!}` audit**  
Review of `{!! !!}` usages in Blade files:
- `resources/views/crm/admin/notification-templates/preview.blade.php` — renders admin-authored email HTML in preview. Content is entered by admin (trusted role), not user-submitted. **Acceptable risk** — document.
- `resources/views/crm/marketing/landing-pages/preview.blade.php` — renders landing page HTML authored by admin. Same rationale. **Acceptable risk**.
- All other `{!! !!}` usages are for Laravel `@csrf`, form method spoofing, or Blade components — all framework-generated.

**Status: LOW RISK** — Admin-authored content is trusted; no user-controlled input rendered unescaped.

---

### A05 — Security Misconfiguration

**Finding: Session SameSite was 'lax'**  
Changed to `'strict'` in `config/session.php` as part of Group AC.  
**Status: FIXED**

**Finding: Session secure cookie**  
Changed default from `null` (browser-default, which is insecure) to `env('APP_ENV') === 'production'` in `config/session.php`.  
**Status: FIXED**

---

### A07 — Identification and Authentication Failures

**Finding: No MFA before Group AC**  
Admin and manager users had no second factor. Now enforced via TOTP (`pragmarx/google2fa-laravel`). `RequireMfa` middleware redirects to setup/verify on first login.  
**Status: FIXED**

**Finding: MFA brute force protection**  
MFA routes (`/crm/mfa/setup`, `/crm/mfa/enable`, `/crm/mfa/verify`) protected by `throttle:5,1` middleware (5 attempts per minute).  
**Status: PASS**

---

### A09 — Security Logging and Monitoring

**Finding: Failed job monitoring**  
`AlertFailedJobsJob` runs daily and notifies admins by email when `failed_jobs` count exceeds 5. Scheduled at 08:00 daily.  
**Status: FIXED in AC**

---

## Action Items (Post-Go-Live)

| Priority | Action | Owner |
|----------|--------|-------|
| High | Execute penetration test per `docs/security/pentest-scope.md` | External engagement |
| High | Configure TLS certificate renewal automation (nginx/infra) | DevOps |
| Medium | Review admin-authored HTML templates for stored XSS quarterly | Security team |
| Low | Enable Composer audit in CI pipeline (`composer audit`) | DevOps |
