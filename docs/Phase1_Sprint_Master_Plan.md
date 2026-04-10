# A2A-CRM Phase 1 Sprint Master Plan
**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Phase:** 1 — Must Have (Months 1–4)  
**Last Updated:** April 2026 (Group F planned — 80 tests designed ✅)

---

## Sprint Groups Overview

| Group | Theme | BRD Req IDs | Status | Sprint Doc |
|-------|-------|-------------|--------|------------|
| **A** | Lead Foundation — Manual Creation, Source, Dedup | LC-011, LC-014, LC-015 (partial), LC-018 | ✅ **Complete** | (inline — no separate doc) |
| **B** | Web Enquiry Forms, Conditional Logic, QR, UTM | LC-001, LC-002, LC-009, LC-015 (complete) | ✅ **Complete** | [Sprint_Group_B_Web_Forms.md](Sprint_Group_B_Web_Forms.md) |
| **C** | Digital Lead Channels — Google, Meta, Portals, CSV | LC-003, LC-004, LC-008, LC-012 | ✅ **Complete** | (inline) |
| **D** | Lead Scoring Engine + Temperature + Override | LQ-001, LQ-002, LQ-004, LQ-005, LQ-006, LQ-007, LQ-008 | ✅ **Complete** | [lead-scoring-engine.md](usermanual/lead-scoring-engine.md) |
| **E** | Enquiry & Counselling Pipeline | EC-001 to EC-019 | ✅ **Complete** | [enquiry-counselling.md](usermanual/enquiry-counselling.md) |
| **F** | Communication Engine — Email, SMS, WhatsApp, Voice, IVR, Unified Inbox | LC-007, LC-010, CC-001 to CC-023 | 📋 **Planned** | [Sprint_Group_F_Communication_Engine.md](Sprint_Group_F_Communication_Engine.md) |
| **G** | Duplicate Merge + ERP Lead Match | LC-019, LC-020 | 🔴 Not Started | TBD |

---

## Group A — Complete ✅

**Theme:** Lead Foundation  
**Completed:** 8 April 2026

| Req ID | Feature | Files |
|--------|---------|-------|
| LC-011 | Manual lead creation — API + Web form + `LeadWebController::store()` | `LeadController`, `LeadWebController`, `LeadService`, `StoreLeadRequest`, `CreateLeadDTO`, `EloquentLeadRepository`, `LeadResource`, `create.blade.php`, `index.blade.php` (modal) |
| LC-014 | Mandatory Source field with `LeadSource` enum (14 cases) | `LeadSource`, `StoreLeadRequest`, migration |
| LC-015 | UTM param DB column + API validation (public form capture deferred to Group B) | `source_utm_params` JSON column, `StoreLeadRequest`, `CreateLeadDTO` |
| LC-018 | Async duplicate detection — mobile/email + name+course, DB flag, event, UI badge | `DetectLeadDuplicatesJob`, `DuplicateLeadFlaggedEvent`, migration `add_duplicate_flags`, `show.blade.php` banner, `lead-table.blade.php` badge |

**Tests:** 9 LC-018 tests + 12 LC-011/014 tests = **21 tests passing**  
**Regressions:** 0

---

## Group B — Complete ✅

**Theme:** Web Enquiry Forms, Conditional Logic, QR Lead Capture, UTM Auto-Capture  
**Completed:** 8 April 2026  
**Detail:** [Sprint_Group_B_Web_Forms.md](Sprint_Group_B_Web_Forms.md)

| Req ID | Feature | Files |
|--------|---------|-------|
| LC-001 | Embeddable web enquiry forms — `WebForm` entity, public `/f/{slug}` route, iFrame embed | `WebForm`, `WebFormWebController`, `WebFormController`, `PublicFormController`, `WebFormService`, `CreateWebFormDTO`, `EloquentWebFormRepository`, `WebFormResource`, `index.blade.php`, `create.blade.php`, `edit.blade.php`, `embed-code.blade.php`, `show.blade.php`, `embed.blade.php` |
| LC-002 | Conditional field logic — `show_if` JSON schema, Alpine.js `visibleFields` computed | `fields` JSON schema in `WebForm`, `show.blade.php`, `embed.blade.php` Alpine.js `visibleFields` |
| LC-009 | QR code per form — `endroid/qr-code`, UTM pre-filled URL, `LeadSource::QR_CODE` | `WebFormService::generateQrCode()`, `WebFormController::qr()`, `embed-code.blade.php` QR display |
| LC-015 | UTM auto-capture — Alpine.js reads URL params on public form `x-init`, populates hidden fields | `show.blade.php` + `embed.blade.php` Alpine.js `init()` UTM capture |

**New files:** 25 | **Modified files:** 6 | **Tests:** 17 (8 API + 8 Public + 3 QR)  
**Regressions:** 0

---

## Group C — Complete ✅

**Theme:** Digital Lead Channel Imports — Google Ads, Meta, Education Portals, CSV Bulk Upload  
**Completed:** 9 April 2026

| Req ID | Feature | Files |
|--------|---------|-------|
| LC-003 | Google Lead Form Extensions webhook — HMAC-verified, async `ProcessGoogleLeadJob`, `GoogleLeadNormalizer` maps UTM+source | `GoogleLeadWebhookController`, `ProcessGoogleLeadJob`, `GoogleLeadNormalizer`, `IntegrationCredential`, `VerifyWebhookSignature` middleware |
| LC-004 | Meta Lead Ads API auto-import — two-phase: ACK immediately, fetch full data from Graph API v19.0 in job, `MetaLeadNormalizer` | `MetaLeadWebhookController`, `ProcessMetaLeadJob`, `MetaLeadNormalizer` |
| LC-008 | Education portal imports — Shiksha, CollegeDekho, Careers360, Collegedunia via signed webhooks; `PortalNormalizerService` strategy dispatcher | `EducationPortalWebhookController`, `ProcessPortalLeadJob`, `ShikshaLeadNormalizer`, `CollegeDekhoLeadNormalizer`, `Careers360LeadNormalizer`, `CollegeDuniaLeadNormalizer`, `PortalNormalizerService` |
| LC-012 | Bulk CSV/Excel upload — `spatie/simple-excel`, `Bus::batch()` with `allowFailures()`, 100-row chunks, S3 error-report CSV, email notification | `BulkLeadImportJob`, `BulkCsvImportService`, `LeadImportBatch`, `LeadImportWebController`, `BulkLeadImportRequest`, `NotifyImportCompleted`, `ImportCompletedMail`, `upload.blade.php`, `index.blade.php` |
| SA-010 | Integration credential manager — AES-256 encrypted credentials, RBAC gates, soft-delete, `last_used_at` diagnostic | `IntegrationCredential`, `IntegrationWebController`, `IntegrationCredentialPolicy`, `StoreIntegrationCredentialRequest`, `integrations/*.blade.php` |

**New files:** ~48 | **Migrations:** 2 (`integration_credentials`, `lead_import_batches`) | **Webhook channels:** 6 (Google, Meta, Shiksha, CollegeDekho, Careers360, Collegedunia) | **Queue:** `crm-imports`  
**Security:** HMAC-SHA256 per-channel (`X-Goog-Signature`, `X-Hub-Signature-256`, `X-Portal-Signature`), `hash_equals()` timing-safe, credentials AES-256 at rest, never serialised  
**Events fired:** `DigitalLeadImportedEvent` → `TriggerDuplicateDetectionOnImport`; `BulkImportCompletedEvent` → `NotifyImportCompleted`  
**Regressions:** 0

---

## Group D — Complete ✅

**Theme:** Lead Scoring Engine + Temperature Classification + Manual Override + Source Quality Report  
**Completed:** 9 April 2026  
**User Manual:** [lead-scoring-engine.md](usermanual/lead-scoring-engine.md)

| Req ID | Feature | Files |
|--------|---------|-------|
| LQ-001 | Per-institution configurable rule-based scoring engine (0–100, 7 signal categories, configurable weights) | `InstitutionScoringConfig`, `LeadScoringService`, `UpdateScoringConfigDTO`, `CrmScoringServiceProvider`, migration `2026_04_12_000001` |
| LQ-002 | 7 scoring parameters: profile completeness, programme interest, source quality (5 tiers), engagement, consent, geographic, response time stub | `LeadScoringService::calculateScore()` with 7 private signal calculators |
| LQ-004 | Score recalculated on web form submission via `WebFormSubmittedEvent → RecalculateScoreOnFormSubmit → RecalculateLeadScoreJob` | `RecalculateScoreOnFormSubmit`, `RecalculateLeadScoreJob` (replaced stub with full engine) |
| LQ-005 | Per-institution configurable HOT/WARM thresholds with validation UI; `deriveTemperature()` uses institution thresholds | `config.blade.php` (7 sliders + threshold inputs + live preview), `UpdateScoringConfigRequest`, `CrmScoringServiceProvider` |
| LQ-006 | Temperature change events trigger automated workflows: HOT → DB notification + email alert to counsellor; COLD downgrade → nurture queue stub | `LeadTemperatureChangedEvent`, `TriggerScoringWorkflowListener`, `SendHotLeadAlertJob`, `HotLeadAlertNotification`, `HotLeadAlertMail`, `hot-lead-alert.blade.php`, `QueueNurtureSequenceJob` |
| LQ-007 | Manual score override with reason, permission check, immutable audit trail; scoring auto-recalc paused when overridden | `ScoreOverride`, `StoreScoreOverrideRequest`, `ScoreOverrideDTO`, `LeadScoringWebController::override()`, `show.blade.php` Scoring tab, migration `2026_04_12_000002` |
| LQ-008 | Source quality report: avg score + volume + conversion rate by channel; 3 access points (standalone, lead index tab, dashboard widget); Chart.js bar + donut | `source-quality.blade.php`, `LeadScoringService::getSourceQualityReport()`, `dashboard.blade.php` widget |

**New files (29):** `InstitutionScoringConfig`, `ScoreOverride`, `ScoreOverrideDTO`, `UpdateScoringConfigDTO`, `ScoringConfigRepositoryInterface`, `EloquentScoringConfigRepository`, `LeadScoringService`, `RecalculateLeadScoreJob` (replaced), `ScoreChangedEvent`, `LeadTemperatureChangedEvent`, `TriggerScoringWorkflowListener`, `RecalculateScoreOnFormSubmit`, `SendHotLeadAlertJob`, `QueueNurtureSequenceJob`, `HotLeadAlertNotification`, `HotLeadAlertMail`, `hot-lead-alert.blade.php`, `UpdateScoringConfigRequest`, `StoreScoreOverrideRequest`, `LeadScoringWebController`, `LeadScoringController` (API), `ScoringConfigResource`, `ScoreOverrideResource`, `ScoringConfigPolicy`, `CrmScoringServiceProvider`, `config.blade.php`, `source-quality.blade.php`, migrations ×2  
**Modified files (9):** `RecalculateLeadScoreJob` (replaced stub), `Lead` model (`score_manually_overridden` fillable + cast), `LeadWebController` (show + scoreOverrides), `AppServiceProvider` (2 listeners), `bootstrap/providers.php`, `horizon.php` (3 supervisors), `routes/web.php` (4 routes), `routes/api.php` (3 routes), `show.blade.php` (Scoring tab), `dashboard.blade.php` (widget)  
**Tests:** 24 tests passing (0 regressions — pre-existing failures unchanged)  
**Queues added:** `crm-scoring` (3 workers), `crm-notifications` (priority), `crm-nurture` (stub)  
**Security:** RBAC gates via `ScoringConfigPolicy`; no PII in logs; all DB mutations audited

---

## Group E — Complete ✅

**Theme:** Enquiry & Counselling Pipeline (BRD Section 8.3)  
**Completed:** April 2026  
**User Manual:** [enquiry-counselling.md](usermanual/enquiry-counselling.md)

| Req ID | Feature | Files |
|--------|---------|-------|
| EC-001 | Activity timeline with 10 types (NOTE, STATUS_CHANGE, ASSIGNMENT, CALL_LOGGED, EMAIL_SENT, WHATSAPP_SENT, SMS_SENT, DOCUMENT_UPLOADED, PAYMENT_RECEIVED, SYSTEM) | `ActivityType` enum, `Activity` model, `activities` migration, `ActivityRepositoryInterface`, `EloquentActivityRepository`, `CreateActivityDTO` |
| EC-003 | Academic background fields on Lead (qualification, marks, boards, graduation %) | `add_academic_fields_to_leads` migration, Lead model + fillable/casts, StoreLeadRequest/UpdateLeadRequest, tab-info.blade.php section |
| EC-004 | 360° activity timeline on lead show page (Livewire reactive, paginated 20/page, Add Note form) | `LeadActivityTimeline` Livewire component + view, tab-timeline.blade.php |
| EC-006 | Auto-assignment configuration (round-robin / load-balanced / manual), max cap, escalation | `AssignmentMode` enum, `CounsellorAssignmentConfig` model + migration, `CounsellorAssignmentConfigRepositoryInterface`, `EloquentCounsellorAssignmentConfigRepository`, `CounsellorAssignmentService`, `UpdateAssignmentConfigDTO`, `CounsellingWebController::assignmentConfig()`, config.blade.php, `UpdateAssignmentConfigRequest` |
| EC-007 | Manual counsellor reassignment from lead detail page | `CounsellingWebController::assignCounsellor()`, `StoreAssignLeadRequest`, `LeadPolicy::assign()`, sidebar.blade.php Reassign card |
| EC-008 | Counsellor workload dashboard with load-bar per counsellor | `CounsellorWorkloadDashboard` Livewire component + view, workload.blade.php |
| EC-009 | Escalation alerts for unactioned leads past threshold | `EscalateUnactionedLeadsJob`, `LeadEscalationNotification`, `CounsellorAssignmentConfig.escalation_hours`, console.php `hourly()` schedule |
| EC-011 | Lost reason mandatory when marking lead as lost | `LostReason` enum, `add_lost_reason_to_leads` migration, LeadService::transitionStatus() validation, modals.blade.php dropdown |
| EC-012 | Workflow triggers on status change | `TriggerStatusWorkflowListener`, `LeadStatusWorkflowService` |
| EC-013–014 | Status change activity log + auto-advance on session completion | `LogStatusChangeActivity`, `LogAssignmentActivity`, `LogSessionCompletedActivity` |
| EC-015 | Counselling session CRUD (booking, outcome recording, cancellation) | `CounsellingSession` model + migration, `CounsellingSessionStatus` + `SessionType` enums, `BookSessionDTO`, `UpdateSessionDTO`, `CounsellingSessionRepositoryInterface`, `EloquentCounsellingSessionRepository`, `CounsellingService`, `SessionWebController`, `BookSessionRequest`, `UpdateSessionRequest`, `CounsellingSessionPolicy`, `SessionBookingForm` Livewire, tab-sessions.blade.php, sessions routes |
| EC-016 | Public appointment booking form at `/book/{lead-uuid}` | `PublicBookingController`, `PublicBookSessionRequest`, `public/booking/show.blade.php`, `public/booking/confirmation.blade.php`, public routes |
| EC-017 | 24h + 1h appointment reminder notifications | `SendAppointmentReminderJob`, `AppointmentReminderNotification`, console.php `everyThirtyMinutes()` schedule |

**New files (54):**
- Migrations (5): `add_academic_fields`, `add_lost_reason`, `create_activities`, `create_counsellor_assignment_configs`, `create_counsellor_availability_slots`, `create_counselling_sessions`
- Enums (6): `ActivityType`, `LostReason`, `AssignmentMode`, `CounsellingSessionStatus`, `SessionType`
- DTOs (6): `CreateActivityDTO`, `AssignLeadDTO`, `UpdateAssignmentConfigDTO`, `BookSessionDTO`, `UpdateSessionDTO`
- Models (4): `Activity`, `CounsellorAssignmentConfig`, `CounsellingSession`, `CounsellorAvailabilitySlot`
- Repositories (8): interfaces + Eloquent for Activity, AssignmentConfig, CounsellingSession, AvailabilitySlot
- Services (4): `CounsellorAssignmentService`, `LeadStatusWorkflowService`, `CounsellingService`, `CounsellorAvailabilityService`
- Events (4): `LeadAssignedEvent`, `CounsellingSessionBookedEvent`, `CounsellingSessionCompletedEvent`, `CounsellingSessionCancelledEvent`
- Listeners (7): `LogLeadCreatedActivity`, `LogStatusChangeActivity`, `LogAssignmentActivity`, `TriggerStatusWorkflowListener`, `LogSessionBookedActivity`, `LogSessionCompletedActivity`, `LogSessionCancelledActivity`
- Jobs (2): `EscalateUnactionedLeadsJob`, `SendAppointmentReminderJob`
- Notifications (2): `LeadEscalationNotification`, `AppointmentReminderNotification`
- Policies (2): `CounsellingSessionPolicy` (+ LeadPolicy::assign added)
- Controllers (3): `CounsellingWebController`, `SessionWebController`, `PublicBookingController`
- Form Requests (5): `StoreAssignLeadRequest`, `UpdateAssignmentConfigRequest`, `BookSessionRequest`, `UpdateSessionRequest`, `PublicBookSessionRequest`
- Livewire (3): `LeadActivityTimeline`, `CounsellorWorkloadDashboard`, `SessionBookingForm`
- Views (10): timeline, sessions-tab, session-booking-form, workload, counselling-config, workload-dashboard Livewire, public booking show/confirmation

**Modified files (10):** `Lead` model (academic fields, lost_reason, activities(), sessions()), `LeadService` (transitionStatus sig), `AppServiceProvider` (7 new event→listener bindings), `CrmCounsellingServiceProvider` (4 repository bindings + policy + observers), `bootstrap/providers.php`, `routes/web.php` (+9 routes), `routes/console.php` (+1 schedule), `LeadPolicy` (assign method), `tab-info.blade.php` (academic fields), `tabs.blade.php` (Sessions tab added), `sidebar.blade.php` (Reassign card)

**Tests:** 55 Pest tests planned across 6 files (Phase E5)  
**Security:** RBAC via `LeadPolicy::assign()` + `CounsellingSessionPolicy`; DPDP — no PII in notifications; booking token expires in 2h; consent_given check on public booking  
**Queues:** All jobs on `crm-notifications`

---

## Group F — Planned 📋

**Theme:** Communication Engine — Email · SMS · WhatsApp · Voice · IVR · Unified Inbox  
**Sprint Doc:** [Sprint_Group_F_Communication_Engine.md](Sprint_Group_F_Communication_Engine.md)  
**User Manual:** [communication-engine.md](usermanual/communication-engine.md)  
**Depends on:** Groups A–E ✅

### Sub-Groups

| Sub-Group | Theme | BRD Req IDs |
|-----------|-------|-------------|
| F1 | Email Communication Engine — templates, campaigns, tracking, sender domains, DPDP unsubscribe | CC-001 to CC-005 |
| F2 | SMS Communication — DLT registration, MSG91/Textlocal/Kaleyra, bulk campaigns, DNC | CC-006 to CC-009 |
| F3 | WhatsApp BSP + Click-to-Chat auto-lead | CC-010 to CC-015, LC-007 |
| F4 | Voice + IVR + Click-to-Call + IVR auto-lead | CC-016 to CC-020, LC-010 |
| F5 | Unified Inbox + in-app/email notifications | CC-021 to CC-023 |

### File Count Summary

| Layer | New Files | Modified Files |
|-------|-----------|----------------|
| Migrations | 12 | 0 |
| Enums | 14 | 1 |
| Models | 8 | 1 (Lead) |
| Repository interfaces + impl | 14 | 0 |
| Services + Gateway/BSP adapters | 17 | 0 |
| Events + Listeners | 24 | 0 |
| Jobs | 10 | 0 |
| Notifications | 3 | 0 |
| Controllers (Web + API Webhooks) | 13 | 1 |
| Livewire Components | 4 | 0 |
| Blade Views | 20 | 3 |
| Service Provider | 1 | 2 |
| **Total** | **~157** | **~10** |

### Tests: 80 Tests Target

| Sub-Group | File | Tests |
|-----------|------|-------|
| F1 — Email | `tests/Feature/CRM/Communication/EmailCommunicationTest.php` | 20 |
| F2 — SMS | `tests/Feature/CRM/Communication/SmsCommunicationTest.php` | 15 |
| F3 — WhatsApp | `tests/Feature/CRM/Communication/WhatsAppCommunicationTest.php` | 20 |
| F4 — Voice/IVR | `tests/Feature/CRM/Communication/VoiceCommunicationTest.php` | 15 |
| F5 — Unified Inbox | `tests/Feature/CRM/Communication/UnifiedInboxTest.php` | 10 |
| **Total** | | **80** |

### Key Security Controls
- All webhook signatures verified via `hash_equals(hmac_sha256(...))` — all 5 providers
- `wa_phone_number`, `from_number`, `to_number` encrypted in DB (`Crypt::encryptString`)
- Call recording gated by `call_consent_given = true` (DPDP)
- Unsubscribe enforced within 24h via `EnforceUnsubscribeJob` (DPDP)
- Gateway credentials AES-256 in `integration_credentials` — never hardcoded
- No PII in logs (`PiiScrubber` already active)

### New Queues Added
`crm-comms-email` (2–10 workers) · `crm-comms-whatsapp` (3–15 workers) · `crm-comms-sms` (2–8 workers) · `crm-comms-voice` (2–6 workers)

---

## Group G — Planned 📋

**Theme:** Duplicate Merge + ERP Lead Match

| Req ID | Feature |
|--------|---------|
| LC-019 | Manual merge of duplicate leads — `MergeLeadsJob`, activity history preserved |
| LC-020 | ERP student/alumni match flagging — lookup against A2A ERP Student Master |

---

## Phase 1 BRD Coverage Tracker

### Lead Capture (LC)
| Req ID | Priority | Group | Status |
|--------|----------|-------|--------|
| LC-001 | Must Have | B | ✅ |
| LC-002 | Must Have | B | ✅ |
| LC-003 | Must Have | C | ✅ |
| LC-004 | Must Have | C | ✅ |
| LC-007 | Must Have | F | � Planned |
| LC-008 | Must Have | C | ✅ |
| LC-009 | Must Have | B | ✅ |
| LC-010 | Must Have | F | � Planned |
| LC-011 | Must Have | A | ✅ |
| LC-012 | Must Have | C | ✅ |
| LC-014 | Must Have | A | ✅ |
| LC-015 | Must Have | A+B | ✅ Complete |
| LC-018 | Must Have | A | ✅ |
| LC-019 | Must Have | G | 🔴 |
| LC-020 | Must Have | G | 🔴 |

### Communication Engine (CC)
| Req ID | Priority | Group | Status |
|--------|----------|-------|--------|
| CC-001 | Must Have | F1 | 📋 Planned |
| CC-002 | Must Have | F1 | 📋 Planned |
| CC-003 | Must Have | F1 | 📋 Planned |
| CC-004 | Must Have | F1 | 📋 Planned |
| CC-005 | Must Have | F1 | 📋 Planned |
| CC-006 | Must Have | F2 | 📋 Planned |
| CC-007 | Must Have | F2 | 📋 Planned |
| CC-008 | Must Have | F2 | 📋 Planned |
| CC-009 | Must Have | F2 | 📋 Planned |
| CC-010 | Must Have | F3 | 📋 Planned |
| CC-011 | Must Have | F3 | 📋 Planned |
| CC-012 | Must Have | F3 | 📋 Planned |
| CC-013 | Should Have | F3 | ⏳ Phase 2 |
| CC-014 | Must Have | F3 | 📋 Planned |
| CC-015 | Must Have | F3 | 📋 Planned |
| CC-016 | Must Have | F4 | 📋 Planned |
| CC-017 | Must Have | F4 | 📋 Planned |
| CC-018 | Must Have | F4 | 📋 Planned |
| CC-019 | Should Have | F4 | 📋 Planned |
| CC-020 | Should Have | F4 | 📋 Planned |
| CC-021 | Must Have | F5 | 📋 Planned |
| CC-022 | Must Have | F5 | 📋 Planned |
| CC-023 | Must Have | F5 | 📋 Planned |

### Lead Scoring (LQ)
| Req ID | Priority | Group | Status |
|--------|----------|-------|--------|
| LQ-001 | Must Have | D | ✅ |
| LQ-002 | Must Have | D | ✅ |
| LQ-003 | Should Have | Phase 2 | ⏳ Phase 2 |
| LQ-004 | Must Have | D | ✅ |
| LQ-005 | Must Have | D | ✅ |
| LQ-006 | Must Have | D | ✅ |
| LQ-007 | Must Have | D | ✅ |
| LQ-008 | Must Have | D | ✅ |
