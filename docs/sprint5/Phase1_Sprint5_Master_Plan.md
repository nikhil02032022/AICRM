# A2A-CRM Phase 1 Sprint 5 Master Plan
**BRD:** MEETCS-BRD-CRM-001 v1.0
**Phase:** 1 - Sprint 5 (AI / EC / AL / NFR Delivery)
**Last Updated:** April 24, 2026 (Group X completed — AI-001 done; Groups Y, Z, AA, AB, AC pending)

---

## Sprint 5 Scope Decision

Sprint 5 closes all remaining open BRD requirement IDs from Phase 1 v1.0. Sprints 1–4 delivered 100% of Must Have requirements with one exception (AI-001, deferred due to dependency sequencing) and 95% of Should Have requirements. Sprint 5 delivers:

1. AI-Assisted Lead Scoring — Claude API behavioural prediction (CRM-AI-001)
2. Video Counselling Integration — Zoom / Google Meet / WebRTC embedded (CRM-EC-018)
3. Walk-in Queue Management — Token-based in-person counselling queue (CRM-EC-019)
4. Alumni Referral Campaigns and Tracking (CRM-AL-002, CRM-AL-003)
5. Alumni NPS Analytics Integration (CRM-AL-004)
6. AI Call Transcription and Summary Generation (CRM-AI-007)
7. Analytics API for Power BI / Tableau (CRM-AR-021)
8. Non-Functional Requirements — Performance, Security, Availability, Maintainability (NFR)

**Already completed in prior sprints and excluded from Sprint 5:**
- All 179 Must Have requirements (Sprints 1–4) except AI-001
- All Should Have requirements except EC-018, EC-019, AL-002, AL-003
- Full DPDP compliance suite (CR-001 to CR-010 — Sprint 4 Group W)
- Alumni lifecycle bridge AL-001 (Sprint 4 Group W)
- Analytics and reporting AR-001 to AR-020 (Sprint 4 Group V)

**Deferred beyond Sprint 5:**
- Mobile App (MB-001 to MB-008) — Phase 2 separate native codebase
- ERP Integration Layer (EI-001 to EI-007, EI-009) — ERP API specification not yet finalised
- Payment gateway full adapters (PayU, CCAvenue) — current stubs acceptable for Phase 1 go-live
- AI call audio transcription (AI-007 partial) — Sprint 5 delivers Claude-based text summarisation; audio-to-text provider deferred to Sprint 6

---

## Sprint Groups Overview

| Group | Theme | BRD Req IDs | Dependency | Status |
|-------|-------|-------------|------------|--------|
| **X** | AI-Assisted Lead Scoring | AI-001 | AiLeadScore (Sprint 2), CommunicationLog, CounsellingSession (Sprint 1) | ✅ Completed (2026-04-24) |
| **Y** | Video Counselling and Walk-in Queue | EC-018, EC-019 | CounsellingSession (Sprint 1), Pusher/Echo setup | ⏳ Pending |
| **Z** | Extended Alumni — Referral and NPS | AL-002, AL-003, AL-004 | AlumniPipeline (Sprint 4 Group W), Lead model | ⏳ Pending |
| **AA** | AI Call Transcription and Summary | AI-007 | CallLog (Sprint 2 Group J), Claude API | ⏳ Pending |
| **AB** | Analytics API for BI Tools | AR-021 | Analytics services (Sprint 4 Group V), Sanctum | ⏳ Pending |
| **AC** | NFR Production Hardening | NFR-P, NFR-SE, NFR-AV, NFR-MT | All prior sprints | ⏳ Pending |

---

## Execution Order

1. Group X and Group Y (parallel — independent start; both add to separate models)
2. Group Z (parallel with X and Y — alumni models independent of EC/AI models)
3. Group AA (after Group X foundations confirm Claude API wiring pattern; can parallel with Y/Z)
4. Group AB (after Group V analytics services confirmed from Sprint 4; can start anytime)
5. Group AC (NFR — can begin database index audit immediately; MFA and security hardening after all features stable)

---

## Group-Wise Design Plan

### Group X — AI-Assisted Lead Scoring
**Req IDs:** AI-001

**Design scope:**
- Aggregate per-lead behavioural signals: source quality, response time to first contact, page views, document completion percentage, payment attempt history, counselling session count, communication frequency
- Build a structured prompt context window using existing AiLeadScore, LeadAttribution, CommunicationLog, CounsellingSession, ApplicationFormDraft records
- Call Claude API (claude-sonnet-4-6) with a structured JSON prompt; parse conversion probability (0.0–1.0), confidence score, and top 3 prediction factors
- Persist prediction in extended AiLeadScore model (new columns: conversion_probability, confidence_score, prediction_factors JSON)
- Trigger prediction refresh on lead stage change, inbound communication, score override, and counselling session creation
- Display conversion probability badge on lead list and lead detail views
- Feed counsellor accept/reject decisions into existing AiSuggestionDecision model for audit and future prompt enrichment
- Log all Claude API calls through existing AiUsageLoggingService

**Primary deliverables:**
- ConversionPredictionService with Claude API integration and signal aggregation
- RefreshConversionPredictionJob queued on crm-ai queue
- Migration to extend ai_lead_scores table
- Probability badge component on lead views
- Test coverage for signal aggregation, API call, persistence, and event triggering

### Group Y — Video Counselling and Walk-in Queue
**Req IDs:** EC-018, EC-019

**Design scope:**

**EC-018 Video Counselling:**
- Strategy-pattern VideoMeetingService with Google Meet (Calendar API OAuth), Zoom (personal room URL field), and WebRTC stub providers
- Generate meeting link on counselling session creation; include in confirmation email and WhatsApp notification
- Join Video Call button on counsellor session view and student portal session card
- Store meeting_link and meeting_provider on counselling_sessions table

**EC-019 Walk-in Queue:**
- Token-based queue management for in-person counselling centres
- Kiosk self-service terminal view for walk-in visitors (extends existing public kiosk route)
- Counsellor view for calling next token, serving, and skipping
- Real-time queue display screen for TV or monitor at reception (unauthenticated, auto-refresh via Pusher broadcast)
- Optional lead stub creation from walk-in token
- Daily analytics: token volume, average wait time, served vs skipped ratio

**Primary deliverables:**
- VideoMeetingService and provider implementations
- WalkInToken model, migration, and controller
- Broadcast event for real-time queue updates
- Queue display and counsellor queue views
- Test coverage for meeting link generation, token lifecycle, and real-time broadcast

### Group Z — Extended Alumni Features
**Req IDs:** AL-002, AL-003, AL-004

**Design scope:**
- AL-002: Alumni referral campaign management (name, description, dates, reward type, reward value, status)
- AL-002: Unique referral code generation per alumni per campaign (8-character alphanumeric, unique)
- AL-002: Code sharing via WhatsApp message template and email
- AL-003: Referral code capture on lead creation via `?ref=CODE` query parameter on web enquiry forms
- AL-003: Lead tagged with referring alumni ID and referral code; sourced as Alumni Referral in attribution
- AL-003: Alumni reward accrual event when referred lead converts to enrolled student
- AL-003: Referral conversion report in Analytics dashboard — leads vs conversions per campaign
- AL-004: Alumni NPS snapshot model; manual-entry UI for admin; optional webhook for A2A Alumni module
- AL-004: NPS trend card added to existing Executive Dashboard

**Primary deliverables:**
- AlumniReferralCampaign and AlumniReferralCode models and migrations
- Referral code capture in PublicFormActor (web forms)
- AlumniReferralService with code generation, tracking, and reward accrual
- AlumniNpsSnapshot model and admin entry UI
- NPS card on Executive Dashboard
- Test coverage for code generation uniqueness, referral tagging, reward event, and NPS display

### Group AA — AI Call Transcription and Summary
**Req IDs:** AI-007

**Design scope:**
- Add transcript_text, transcription_summary (JSON), transcription_status columns to call_logs table
- Transcript input: counsellor pastes call transcript text on call completion form (Sprint 5 scope); audio-to-text provider deferred to Sprint 6
- TranscribeCallJob dispatched when call disposition is saved with a non-empty transcript
- Claude API prompt: extract key interests, objections, agreed next steps, lead temperature assessment; return structured JSON
- Auto-populate disposition notes from AI summary if counsellor leaves the field blank
- Transcription panel on call log detail view: AI summary card (expanded) and raw transcript (collapsible)
- All Claude calls logged via AiUsageLoggingService; PII scrubbed via PayloadRedactor before logging

**Primary deliverables:**
- Migration to extend call_logs table
- CallTranscriptionService with Claude API integration
- TranscribeCallJob queued on crm-ai queue
- Updated call completion form with transcript input field
- Transcription panel on call detail view
- Test coverage for transcript processing, summary structure, PII scrubbing, and auto-populate

### Group AB — Analytics API for BI Tools
**Req IDs:** AR-021

**Design scope:**
- REST API endpoints for analytics data access by external BI tools (Power BI, Tableau)
- Laravel Sanctum token-based authentication; tokens issued and managed from Admin → System Config
- Endpoints: lead funnel metrics, application pipeline stage counts, fee collection summary, counsellor performance metrics
- All endpoints: institution-scoped via API token claim, paginated, date-range filterable, JSON response
- OpenAPI 3.0 specification document generated at docs/api/analytics-api.yaml
- Token management UI card added to existing system-config admin view

**Primary deliverables:**
- AnalyticsApiController with 4 data endpoints
- API routes under /api/crm/v1/analytics/ with Sanctum middleware
- Token management UI in Admin
- OpenAPI 3.0 spec file
- Test coverage for authentication, institution scoping, date filtering, and response structure

### Group AC — NFR Production Hardening
**Req IDs:** NFR-P-001 to NFR-P-005, NFR-SE-001 to NFR-SE-007, NFR-AV-001 to NFR-AV-004, NFR-MT-001 to NFR-MT-004

**Design scope:**

**Performance (NFR-P):**
- Database index audit: EXPLAIN on top 10 dashboard and lead-list queries; composite indexes migration
- Redis cache layer on dashboard stat queries in DashboardController (5-minute TTL)
- Eager loading audit: fix top 5 N+1 patterns across analytics and lead list controllers
- Horizon supervisor tuning for crm-ai (2 workers), crm-comms-email (3), crm-comms-sms (3)
- Vite production build verification with chunk splitting and gzip

**Security (NFR-SE):**
- TOTP-based MFA for users with admin or manager role (pragmarx/google2fa-laravel)
- Session hardening: lifetime 120 min, secure cookie, SameSite=Strict in production config
- Configurable IP whitelist for admin routes via AdminIpWhitelist middleware and System Config UI
- OWASP Top 10 code review with documented findings
- Penetration test scope document for pre-go-live engagement

**Availability (NFR-AV):**
- Health check endpoint GET /health returning queue, DB, and Redis status for load balancer probes
- Dead-letter queue monitoring: failed_jobs review process documented
- Graceful shutdown verification for Horizon queue workers

**Maintainability (NFR-MT):**
- php artisan test --coverage baseline report with target ≥70% coverage
- Scribe or l5-swagger API documentation for all /api/crm/* routes
- CLAUDE.md and README updated with queue startup, Horizon config, and environment variable reference

**Primary deliverables:**
- Performance index migration
- Redis cache calls in DashboardController
- MFA package integration and TOTP flow
- AdminIpWhitelist middleware and config
- HealthController and /health route
- OWASP review document and pentest scope document
- API documentation generation
- Test coverage baseline report
- Updated horizon.php supervisor configuration

---

## Mandatory Deliverables After Each Group

1. User Manual
   - Feature usage steps
   - Role-based usage notes
   - Screenshots where applicable

2. Test Cases
   - Scenario ID
   - Preconditions
   - Steps
   - Expected result
   - Actual result and status

3. Master Tracker Update
   - Mark status: Planned / In Progress / Completed / Blocked
   - Update dependencies and remarks
   - Add completion date and evidence note

---

## Documentation Structure for Sprint 5

Sprint 5 documents should be maintained under the sprint5 folder using consistent names:

- Phase1_Sprint5_Master_Plan.md
- sprint5_group_X_ai_conversion_scoring.md
- sprint5_group_Y_video_counselling_walkin.md
- sprint5_group_Z_alumni_referral.md
- sprint5_group_AA_call_transcription.md
- sprint5_group_AB_analytics_api.md
- sprint5_group_AC_nfr_production.md
- test-cases/sprint5_group_X_test_cases.md
- test-cases/sprint5_group_Y_test_cases.md
- test-cases/sprint5_group_Z_test_cases.md
- test-cases/sprint5_group_AA_test_cases.md
- test-cases/sprint5_group_AB_test_cases.md
- test-cases/sprint5_group_AC_test_cases.md

---

## Tracker Update Rule

After each group completion:

1. Update this master file status table.
2. Update the group-specific implementation log.
3. Add/refresh group test case document.
4. Update consolidated Sprint 5 user manual entry.
5. Record blockers, dependency changes, and closure remarks.

---

## Notes

- All web flows must use web controllers and Blade/Livewire views; external consumers use versioned API routes only.
- All Claude API calls must be logged through AiUsageLoggingService and PII scrubbed via PayloadRedactor before logging (DPDP compliance).
- Group X and Group AA both use the crm-ai queue and Claude API — ensure horizon worker count is adequate before deploying.
- Group AC NFR hardening must be done in a non-destructive order: read-only changes (indexes, cache) before configuration changes (MFA, IP whitelist), before security-gated changes (session config).
- Walk-in queue real-time display (EC-019) reuses existing Pusher/Echo configuration — verify broadcast driver is set in production .env before deployment.
- Alumni referral code capture (AL-002/003) modifies PublicFormActor which is a high-traffic class — test thoroughly before deployment.

---

## Sprint 5 Status Snapshot (2026-04-24)

| Module | Group | Status | Open Items |
|---|---|---|---|
| AI-Assisted Lead Scoring (AI-001) | X | ✅ Completed (2026-04-24) | 20/20 tests passing; ANTHROPIC_API_KEY required in production .env |
| Video Counselling and Walk-in Queue (EC-018, EC-019) | Y | ⏳ Pending | — |
| Extended Alumni Referral and NPS (AL-002, AL-003, AL-004) | Z | ⏳ Pending | — |
| AI Call Transcription (AI-007) | AA | ⏳ Pending | — |
| Analytics API for BI Tools (AR-021) | AB | ⏳ Pending | — |
| NFR Production Hardening | AC | ⏳ Pending | — |

---

## Sprint 5 BRD Coverage Tracker

| Req ID | Priority | Group | Status |
|--------|----------|-------|--------|
| CRM-AI-001 | Must Have | X | ✅ Completed (2026-04-24) |
| CRM-EC-018 | Should Have | Y | ⏳ Pending |
| CRM-EC-019 | Should Have | Y | ⏳ Pending |
| CRM-AL-002 | Should Have | Z | ⏳ Pending |
| CRM-AL-003 | Should Have | Z | ⏳ Pending |
| CRM-AL-004 | Could Have | Z | ⏳ Pending |
| CRM-AI-007 | Could Have | AA | ⏳ Pending |
| CRM-AR-021 | Could Have | AB | ⏳ Pending |
| NFR-P-001 | NFR | AC | ⏳ Pending |
| NFR-P-002 | NFR | AC | ⏳ Pending |
| NFR-P-003 | NFR | AC | ⏳ Pending |
| NFR-P-004 | NFR | AC | ⏳ Pending |
| NFR-P-005 | NFR | AC | ⏳ Pending |
| NFR-SE-001 | NFR | AC | ⏳ Pending |
| NFR-SE-002 | NFR | AC | ⏳ Pending |
| NFR-SE-003 | NFR | AC | ⏳ Pending |
| NFR-SE-004 | NFR | AC | ⏳ Pending |
| NFR-SE-005 | NFR | AC | ⏳ Pending |
| NFR-SE-006 | NFR | AC | ⏳ Pending |
| NFR-SE-007 | NFR | AC | ⏳ Pending |
| NFR-AV-001 | NFR | AC | ⏳ Pending |
| NFR-AV-002 | NFR | AC | ⏳ Pending |
| NFR-AV-003 | NFR | AC | ⏳ Pending |
| NFR-AV-004 | NFR | AC | ⏳ Pending |
| NFR-MT-001 | NFR | AC | ⏳ Pending |
| NFR-MT-002 | NFR | AC | ⏳ Pending |
| NFR-MT-003 | NFR | AC | ⏳ Pending |
| NFR-MT-004 | NFR | AC | ⏳ Pending |

---

## Phase 1 BRD Coverage After Sprint 5 (Projected)

| Priority | Sprint 1–4 | Sprint 5 | Total | Coverage |
|----------|-----------|----------|-------|----------|
| Must Have (179) | 178 | 1 (AI-001) ✅ | **179/179** | **100%** ✅ |
| Should Have (55) | 52 | 3 (EC-018, EC-019, AL-002, AL-003) | **56/55\*** | **100%** ✅ |
| Could Have (5) | 0 | 3 (AL-004, AI-007, AR-021) | **3/5** | **60%** |
| Mobile (Phase 2) | 0 | 0 | 0/8 | Deferred |
| ERP (TBD) | 0 | 0 | 0/9 | Deferred |

\* AL-002 and AL-003 are Should Have; count may vary by final BRD tally.
