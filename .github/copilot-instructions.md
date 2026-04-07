# A2A-CRM — GitHub Copilot Workspace Instructions

## Project Identity

**Product:** A2A Educational CRM — part of the Admissions-2-Alumni (A2A) ERP platform  
**Company:** MEETCS Pvt. Ltd.  
**BRD Reference:** MEETCS-BRD-CRM-001 v1.0  
**Stack:** Laravel (PHP 8.2+) · Blade Templates · Tailwind CSS · Alpine.js · Livewire · MySQL 8 · Redis · Laravel Horizon (queues)  
**AI Layer:** Anthropic Claude API (existing MEETCS integration)  
**Mobile:** React Native (iOS + Android)

---

## Architecture Overview

A2A-CRM is a **native module within the A2A ERP**. It is NOT a standalone CRM. Every design decision must account for:

1. **Seamless ERP integration** — Lead → Student → Alumni data flows without re-entry across A2A modules.
2. **Multi-institution, multi-campus tenancy** — Full data segregation using `institution_id` + `campus_id` scoping on every query.
3. **19 BRD modules** — Lead Capture · Lead Scoring · Enquiry & Counselling · Application Pipeline · Communication Engine · Marketing Automation · Fee & Payments · Document Management · Tasks & Follow-ups · Telecalling · Student Portal · Agent Management · Alumni Bridge · Analytics · AI/Agentic Layer · Mobile App · ERP Integration · Third-Party Integrations · System Administration.
4. **DPDP Act 2023 compliance** — Every feature touching PII must enforce consent, minimisation, and erasure. Non-negotiable.
5. **Concurrent load** — Minimum 500 concurrent users per institution; API responses ≤ 500ms at P95.

---

## Tech Stack Conventions

### Backend (Laravel)
- PHP 8.2+ with strict types on every file: `declare(strict_types=1);`
- Service layer pattern: `app/Services/CRM/` — never put business logic in controllers or models.
- Repository pattern for all database access: `app/Repositories/CRM/`
- All multi-tenant queries **must** use the `InstitutionScope` global scope — never raw queries without `institution_id` filter.
- Events and Listeners for all CRM state transitions (lead status changes, payment events, document updates).
- Jobs and Queues (Laravel Horizon) for all async operations: bulk emails, SMS, score recalculation, AI calls.
- Form Requests for all validation — no inline `$request->validate()` in controllers.
- API Resources (`JsonResource`) for all API responses — no raw model serialization.
- Never expose internal IDs in URLs — use UUIDs (`Str::uuid()`).

### Frontend (Blade + Tailwind)
- Laravel Blade templates (`.blade.php`) for all web views — no SPA framework.
- **Tailwind CSS** utility classes for styling; `@class` Blade directive for conditional classes.
- **Alpine.js v3** for lightweight client-side interactivity: dropdowns, modals, tabs, toggles.
- **Laravel Livewire v3** for reactive server-driven components: live search, data tables, form wizards.
- **Chart.js** for analytics charts via `<canvas>` elements; data passed via `@json()` blade helper.
- **Vite** for asset bundling — always use `@vite(['resources/css/app.css','resources/js/app.js'])` directive.
- Never use inline `style` attributes, inline `<script>` blocks with business logic, or jQuery.
- Output escaping: always `{{ }}` — never `{!! !!}` with unsanitised user data (XSS prevention).

### Database
- All migrations must be reversible — every `up()` has a `down()`.
- Indexes on: `institution_id`, `campus_id`, `mobile`, `email`, `status`, `lead_score`, `assigned_counsellor_id`, `created_at` for the leads table.
- Soft deletes (`SoftDeletes` trait) on all CRM core entities — hard delete is prohibited for lead/application/payment records.
- DPDP anonymisation uses `anonymisePII()` method, not deletion.

### API Design
- All APIs versioned: `/api/v1/crm/...`
- Standard response envelope: `{ success, data, message, meta }` where meta includes pagination.
- Error responses: `{ success: false, error: { code, message, field? } }`
- All endpoints authenticated via Laravel Sanctum with RBAC gate checks.

---

## DPDP Act 2023 — Non-Negotiable Rules

1. **Consent at capture** — Every lead creation endpoint must record `consent_given: bool`, `consent_timestamp`, `consent_ip`, `consent_form_version`.
2. **No PII in logs** — Never log names, mobile numbers, Aadhaar, or email in application logs.
3. **Encryption at rest** — Documents stored via encrypted S3; PII fields encrypted using `Crypt::encryptString()` where column-level encryption applies.
4. **Data in India** — AWS ap-south-1 (Mumbai) only. Never configure cross-region replication to non-India regions.
5. **Right to erasure** — `AnonymisePIIJob` must replace PII fields with deterministic anonymised values while preserving aggregate analytics.
6. **Opt-out** — Unsubscribe/DNC operations must take effect within 24 hours and be idempotent.
7. **Call recording consent** — `call_consent_given` must be `true` before any recording starts.

---

## Domain Vocabulary (use consistently in code)

| Domain Term | Code Symbol |
|---|---|
| Lead / Enquiry | `Lead` (model), `leads` (table) |
| Counsellor | `Counsellor` (user role) |
| Programme | `Programme` (synced from A2A ERP) |
| Admission Cycle | `AdmissionCycle` |
| Lead Stage/Status | `LeadStatus` enum |
| Lead Temperature | `LeadTemperature` enum: HOT / WARM / COLD / LOST / CONVERTED |
| Conversion (CRM → ERP) | `convertToStudent()` — triggers `LeadConvertedToStudentEvent` |
| Channel Partner | `Agent` (model) |
| Offer Letter | `OfferLetter` (model/document type) |
| Communication Log | `ActivityLog` (polymorphic, covers all channels) |

---

## Security Requirements (OWASP Top 10)

- **A01 Broken Access Control** — Always check RBAC gates: `Gate::authorize('crm.leads.view', $lead)`. Institution-scoped checks must never be skipped.
- **A03 Injection** — Use Eloquent or query builder with bindings exclusively. No raw SQL with interpolated user input.
- **A05 Security Misconfiguration** — No `.env` values committed. API credentials in `integration_credentials` table (AES-256 encrypted).
- **A07 Auth Failures** — MFA enforced for all non-applicant roles. Session timeout at 8 hours idle.
- **A09 Logging Failures** — All CRM data mutations write to `audit_logs` table: `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `user_id`, `institution_id`, `timestamp`.

---

## BRD Requirement Traceability

Always reference the BRD Req ID in code comments for non-trivial implementations:

```php
// BRD: CRM-LC-018 — Auto-detect duplicate leads on mobile/email match
public function detectDuplicates(CreateLeadRequest $request): Collection
```

```typescript
// BRD: CRM-AI-002 — Next Best Action recommendation display
```

---

## Custom Agents Available

| Agent | Purpose | Invoke with |
|---|---|---|
| `@lead-manager` | Lead capture, scoring, counselling flows | `@lead-manager` in chat |
| `@admissions-pipeline` | Application forms, pipeline, ERP handoff | `@admissions-pipeline` |
| `@communication-engine` | Email/SMS/WhatsApp/IVR implementation | `@communication-engine` |
| `@ai-intelligence` | Anthropic API integration, scoring, NBA | `@ai-intelligence` |
| `@erp-integrator` | A2A ERP sync, Student Master conversion | `@erp-integrator` |
| `@analytics-reporter` | Dashboards, reports, KPIs | `@analytics-reporter` |
| `@crm-architect` | Architecture, NFRs, scalability | `@crm-architect` |
| `@brd-analyst` | BRD traceability, requirements validation | `@brd-analyst` |

---

## Skills Available

| Skill | Purpose |
|---|---|
| `/crm-module-builder` | Scaffold a complete CRM feature module end-to-end |
| `/brd-traceability` | Map existing code back to BRD requirements |
| `/a2a-erp-integration` | Build integration points against A2A ERP APIs |
| `/ui-ux-pro-max` | Design intelligence for Blade/Tailwind UI — styles, colors, accessibility, Livewire/Alpine patterns |

---

## Do NOT

- Do not create any CRM feature that bypasses `institution_id` scoping.
- Do not store PII in Redis cache without TTL and encryption.
- Do not expose stack traces or Eloquent errors to API consumers.
- Do not use `fillable = ['*']` mass assignment on CRM models.
- Do not call Anthropic API synchronously in a web request — always dispatch a job.
- Do not hard-code programme IDs, fee amounts, or gateway credentials.
- Do not merge duplicate lead records without preserving full activity history.
