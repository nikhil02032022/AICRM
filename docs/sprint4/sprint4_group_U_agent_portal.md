# Sprint 4 - Group U: Agent and Channel Partner Portal

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** U
**Module:** Agent and Channel Partner Management
**Req IDs:** CRM-AG-001, CRM-AG-002, CRM-AG-003, CRM-AG-004, CRM-AG-005, CRM-AG-007
**Status:** ✅ Completed (2026-04-22)
**Dependencies:** Lead model (Sprint 1 Group A), LC-014 Source field (Sprint 1 Group A), FM enrolment event (Sprint 3 Group O), AG-006 commission approval (Sprint 2 Group L ✅), AG-008 agent comms (Sprint 2 Group L ✅)

---

## Objective

Enable agent and channel partner onboarding, referral-linked lead attribution, a self-service agent portal for lead submission and tracking, commission structure configuration, and automated commission accrual on enrolment.

## In Scope

1. Agent profile management (contact details, agreement terms, active programmes, performance history).
2. Unique referral link and code per agent for lead source attribution.
3. Agent portal — submit leads, track lead status, view conversion dashboard.
4. Commission structure configuration per agent agreement (per enrolment, per application, percentage of fee).
5. Commission accrual auto-calculation on enrolment confirmation.
6. Agent performance report (leads submitted, conversions, revenue generated, commissions earned).

## Out of Scope

- AG-006 commission approval workflow — completed Sprint 2 Group L.
- AG-008 bulk communication to agent network — completed Sprint 2 Group L.
- Commission payout processing integration with external payment systems (NEFT/RTGS) — out of scope for v1.0.

## Dependencies

1. `Lead` model and `Source` field (LC-014) from Sprint 1 Group A.
2. Enrolment confirmed event from Sprint 3 Group N (`ApplicationStatusChanged` with `enrolled` status).
3. `Payment` model from Sprint 3 Group O for fee-percentage commission calculation.
4. `CommissionApprovalWorkflow` from Sprint 2 Group L (AG-006).

## Design Notes

1. Agent portal runs on a separate route prefix (`/agent-portal`) with its own authenticated layout.
2. Agents authenticate via standard email + password; separate Guard from the CRM staff guard.
3. Referral codes are embedded in a UTM-style query parameter (`?ref=CODE`) on all shareable links.
4. Lead source attribution: when a lead arrives via a referral link, `lead.source` is set to `Agent` and `lead.agent_id` is populated.
5. Commission accrual fires via `EnrolmentObserver` on the `Application` model when status transitions to `enrolled`.
6. Agent performance report is a read-only view; uses same export infrastructure as Group V.

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for agent onboarding and portal usage.
3. Group U test cases document (`test-cases/sprint4_group_U_test_cases.md`).
4. Master tracker status and remarks update.

## Acceptance Gates

1. Agent profile can be created, edited, and deactivated by admissions manager.
2. Each agent has a unique referral code; shareable lead capture links embed the code.
3. Leads submitted via referral link are automatically attributed to the correct agent.
4. Agent portal shows submitted leads with status, last updated, and counsellor assigned.
5. Commission structure can be configured independently per agent agreement and programme.
6. On enrolment confirmation, commission accrues automatically and a commission record is created.
7. Agent performance report shows correct lead counts, conversion rates, revenue, and accrued commissions.
8. No cross-institution agent data visibility.

## Risks and Mitigation

1. Referral code collision across institutions:
   Mitigation: prefix referral codes with institution short-code (e.g., `INST01-AG-4F7C`).
2. Commission calculation on partial-fee applicants:
   Mitigation: configurable trigger point (application fee / seat fee / full fee paid); default to enrolment confirmed.

## Exit Criteria

1. AG-001, AG-002, AG-003, AG-004, AG-005, AG-007 marked completed in master tracker.
2. ~18 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. QA sign-off recorded.

---

## File Manifest

### Migrations
- `create_agents_table.php` — institution_id, name, email, mobile, agreement_start, agreement_end, status, notes
- `create_agent_commission_structures_table.php` — agent_id, programme_id, structure_type (per_enrolment/per_application/percentage_fee), amount, percentage, effective_from, effective_to
- `create_agent_referral_codes_table.php` — agent_id, code (unique), url_slug, total_leads, total_conversions
- `create_agent_commission_accruals_table.php` — agent_id, application_id, lead_id, programme_id, structure_id, accrual_basis_amount, commission_amount, status (pending/approved/paid), accrued_at

### Enums
- `App\Enums\CRM\Agents\AgentStatus` — Active, Inactive, Suspended
- `App\Enums\CRM\Agents\CommissionStructureType` — PerEnrolment, PerApplication, PercentageFee
- `App\Enums\CRM\Agents\CommissionAccrualStatus` — Pending, Approved, Paid, Reversed

### Models
- `App\Models\CRM\Agents\Agent`
- `App\Models\CRM\Agents\AgentCommissionStructure`
- `App\Models\CRM\Agents\AgentReferralCode`
- `App\Models\CRM\Agents\AgentCommissionAccrual`

### Services
- `App\Services\CRM\Agents\AgentService` — CRUD, deactivate, search
- `App\Services\CRM\Agents\AgentReferralService` — generate referral code, resolve incoming referral from query param, attribute lead
- `App\Services\CRM\Agents\CommissionAccrualService` — calculate commission amount from active structure, create accrual record
- `App\Services\CRM\Agents\AgentReportService` — aggregate leads, conversions, revenue, commissions per agent

### Observers
- `App\Observers\CRM\Agents\EnrolmentCommissionObserver` — listens on `Application` status → `enrolled`; calls `CommissionAccrualService::accrue()`

### Controllers (CRM Admin — `/crm/agents`)
- `App\Http\Controllers\CRM\Agents\AgentController` — index, create, store, edit, update, destroy
- `App\Http\Controllers\CRM\Agents\AgentCommissionStructureController` — index, create, store, edit, update
- `App\Http\Controllers\CRM\Agents\AgentReferralController` — show (referral code card + links)
- `App\Http\Controllers\CRM\Agents\AgentReportController` — index (performance report)

### Controllers (Agent Portal — `/agent-portal`)
- `App\Http\Controllers\CRM\AgentPortal\AgentPortalAuthController` — login, logout
- `App\Http\Controllers\CRM\AgentPortal\AgentPortalDashboardController` — index
- `App\Http\Controllers\CRM\AgentPortal\AgentPortalLeadController` — index (submitted leads), store (submit new lead)

### Controllers (API)
- `App\Http\Controllers\Api\V1\CRM\Agents\AgentApiController`
- `App\Http\Controllers\Api\V1\CRM\Agents\AgentCommissionApiController`

### Views (Blade)
- `resources/views/crm/agents/index.blade.php`
- `resources/views/crm/agents/create.blade.php`
- `resources/views/crm/agents/edit.blade.php`
- `resources/views/crm/agents/commission/index.blade.php`
- `resources/views/crm/agents/commission/create.blade.php`
- `resources/views/crm/agents/referral.blade.php`
- `resources/views/crm/agents/report.blade.php`
- `resources/views/agent-portal/layouts/app.blade.php`
- `resources/views/agent-portal/dashboard.blade.php`
- `resources/views/agent-portal/leads/index.blade.php`
- `resources/views/agent-portal/leads/create.blade.php`

### Middleware
- `App\Http\Middleware\CRM\AgentPortal\AgentAuthenticate`

### Policies
- `App\Policies\CRM\Agents\AgentPolicy`
- `App\Policies\CRM\Agents\AgentPortalPolicy`

### Seeders
- `Database\Seeders\CRM\Agents\AgentRolePermissionSeeder`

### Tests
- `tests/Unit/CRM/Agents/AgentReferralServiceTest.php`
- `tests/Unit/CRM/Agents/CommissionAccrualServiceTest.php`
- `tests/Unit/CRM/Agents/AgentReportServiceTest.php`
- `tests/Feature/CRM/Agents/AgentCrudTest.php`
- `tests/Feature/CRM/Agents/AgentCommissionStructureTest.php`
- `tests/Feature/CRM/Agents/AgentReferralAttributionTest.php`
- `tests/Feature/CRM/Agents/AgentPortalLeadSubmitTest.php`
- `tests/Feature/CRM/Agents/EnrolmentCommissionAccrualTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| AG-001 | Agent profile management | `AgentService`, `AgentController`, `agents/` views |
| AG-002 | Unique referral link/code per agent | `AgentReferralService`, `AgentReferralCode` model, `referral.blade.php` |
| AG-003 | Agent portal (submit leads, track status, dashboard) | `AgentPortalDashboardController`, `AgentPortalLeadController`, agent-portal views |
| AG-004 | Commission structures per agent agreement | `AgentCommissionStructure` model, `AgentCommissionStructureController` |
| AG-005 | Commission accrual auto-calculation on enrolment | `EnrolmentCommissionObserver`, `CommissionAccrualService` |
| AG-007 | Agent performance report | `AgentReportService`, `AgentReportController`, `report.blade.php` |

---

## Security Checklist

- [x] Agent portal uses separate `agent` guard; cannot access CRM staff routes.
- [x] Agent can only view leads they submitted (policy on `lead.agent_id`).
- [x] Referral code resolution validates institution scope before attributing lead.
- [x] Commission accruals are immutable after approval (no backdating or override without audit trail).
- [x] Agent profile CRUD restricted to `admissions_manager` and above roles.

---

## Implementation Log

### Completion Date: April 22, 2026

#### Enums
- `App\Enums\CRM\Agents\AgentStatus` — Active, Inactive, Suspended
- `App\Enums\CRM\Agents\CommissionStructureType` — PerEnrolment, PerApplication, PercentageFee
- `App\Enums\CRM\Agents\CommissionAccrualStatus` — Pending, Approved, Paid, Reversed
- `App\Enums\CRM\LeadSource::AGENT` — Added `agent` case for referral-attributed leads

#### Migrations (in order)
1. `2026_06_01_000001_create_agents_table` — Agent profile store with email+password auth columns
2. `2026_06_01_000002_create_agent_sessions_table` — Portal session cookie auth (mirrors portal_sessions)
3. `2026_06_01_000003_create_agent_referral_codes_table` — Unique referral codes, institution-prefixed
4. `2026_06_01_000004_create_agent_commission_structures_table` — Per-agent, per-programme commission configuration
5. `2026_06_01_000005_create_agent_commission_accruals_table` — Auto-accrued commission records
6. `2026_06_01_000006_update_leads_agent_id_fk_to_agents` — FK constraint from leads.agent_id → agents.id

#### Models
- `App\Models\CRM\Agents\Agent` — Profile, password auth, InstitutionScope
- `App\Models\CRM\Agents\AgentSession` — Portal session token store
- `App\Models\CRM\Agents\AgentReferralCode` — Referral code with total_leads/conversions counters
- `App\Models\CRM\Agents\AgentCommissionStructure` — Commission rate configuration with `activeAt()` scope
- `App\Models\CRM\Agents\AgentCommissionAccrual` — Auto-accrued record; immutable after Approved/Paid
- `App\Models\CRM\Lead` — Added `agent()` BelongsTo relationship

#### Services
- `AgentService` — CRUD, deactivate, search
- `AgentAuthService` — Email+password portal auth, session issue/resolve/logout
- `AgentReferralService` — Code generation, ?ref= resolution, lead attribution, counter increments
- `CommissionAccrualService` — Structure lookup, amount calculation (PerEnrolment/PerApplication/PercentageFee), accrual creation
- `AgentReportService` — Aggregated metrics per agent (leads, conversions, revenue, commission breakdown)

#### Observer + Service Provider
- `App\Observers\CRM\Agents\EnrolmentCommissionObserver` — Triggers `CommissionAccrualService::accrue()` on ENROLLED transition
- `App\Providers\CRM\CrmAgentServiceProvider` — Registers observer on Application model, registers AgentPolicy
- Registered in `bootstrap/providers.php`

#### Middleware
- `App\Http\Middleware\CRM\AgentPortal\AgentAuthenticate` — Cookie-based agent session auth
- Registered as alias `agent.portal.auth` in `bootstrap/app.php`

#### Policies
- `App\Policies\CRM\Agents\AgentPolicy` — Admissions manager + above; institution-scoped
- `App\Policies\CRM\Agents\AgentPortalPolicy` — Agent portal data scoping

#### Controllers (CRM Admin)
- `AgentController` — CRUD (index, create, store, edit, update, destroy)
- `AgentCommissionStructureController` — Nested under Agent; index, create, store, edit, update
- `AgentReferralController` — show (referral card with copy-to-clipboard link)
- `AgentReportController` — Performance report with agent + date filters

#### Controllers (Agent Portal)
- `AgentPortalAuthController` — showLogin, login, logout
- `AgentPortalDashboardController` — KPI stats via AgentReportService
- `AgentPortalLeadController` — index (own leads only), create, store

#### API Controllers
- `AgentApiController` — index, show, store, update (Sanctum)
- `AgentCommissionApiController` — index (accruals), show

#### Routes
- CRM admin routes under `/crm/agents` with `crm.agents.view` gate
- Agent portal routes under `/agent-portal` with `agent.portal.auth` middleware
- API routes under `/api/v1/crm/agents` with `auth:sanctum`

#### Views (Blade)
- `crm/agents/index.blade.php` — Paginated agent table with search + status filter
- `crm/agents/create.blade.php` — Create form with Alpine.js
- `crm/agents/edit.blade.php` — Edit + deactivate
- `crm/agents/referral.blade.php` — Referral card with copy-to-clipboard
- `crm/agents/commission/index.blade.php` — Structure list with type badges
- `crm/agents/commission/create.blade.php` — Alpine.js type toggle (amount vs percentage)
- `crm/agents/commission/edit.blade.php`
- `crm/agents/report.blade.php` — Agent performance report table
- `resources/views/components/layouts/agent-portal-app.blade.php` — Portal layout component (`<x-layouts.agent-portal-app>`)
- `agent-portal/login.blade.php` — Email + password + institution code login
- `agent-portal/dashboard.blade.php` — KPI tiles + recent leads
- `agent-portal/leads/index.blade.php` — Lead table (own leads only)
- `agent-portal/leads/create.blade.php` — Lead submission form with DPDP consent

#### Seeder
- `Database\Seeders\CRM\Agents\AgentRolePermissionSeeder` — 8 agent permissions; assigned to admissions_manager, admissions_director, institution-admin, super-admin, senior-counsellor

#### Tests
- 3 Unit: `AgentReferralServiceTest`, `CommissionAccrualServiceTest`, `AgentReportServiceTest`
- 5 Feature: `AgentCrudTest`, `AgentCommissionStructureTest`, `AgentReferralAttributionTest`, `AgentPortalLeadSubmitTest`, `EnrolmentCommissionAccrualTest`
- Total: 18 test cases — all passing

#### Known Decisions
- Agent entity is separate from `User` (dedicated `agents` table with own auth)
- Agent portal auth follows student portal cookie pattern (not Laravel guard/config/auth.php)
- `LeadSource::AGENT` added to existing enum for referral-attributed leads
- `agent_commission_accruals` are separate from `agent_commissions` (Sprint 2 approval workflow) — accruals feed into the approval pipeline as pending records
- Referral code format: `{INST_SHORT}-AG-{4HEX}` ensures global uniqueness across institutions
