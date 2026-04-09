# A2A-CRM Phase 1 Sprint Master Plan
**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Phase:** 1 — Must Have (Months 1–4)  
**Last Updated:** 9 April 2026 (Group D complete)

---

## Sprint Groups Overview

| Group | Theme | BRD Req IDs | Status | Sprint Doc |
|-------|-------|-------------|--------|------------|
| **A** | Lead Foundation — Manual Creation, Source, Dedup | LC-011, LC-014, LC-015 (partial), LC-018 | ✅ **Complete** | (inline — no separate doc) |
| **B** | Web Enquiry Forms, Conditional Logic, QR, UTM | LC-001, LC-002, LC-009, LC-015 (complete) | ✅ **Complete** | [Sprint_Group_B_Web_Forms.md](Sprint_Group_B_Web_Forms.md) |
| **C** | Digital Lead Channels — Google, Meta, Portals, CSV | LC-003, LC-004, LC-008, LC-012 | ✅ **Complete** | (inline) |
| **D** | Lead Scoring Engine + Temperature + Override | LQ-001, LQ-002, LQ-004, LQ-005, LQ-006, LQ-007, LQ-008 | ✅ **Complete** | [lead-scoring-engine.md](usermanual/lead-scoring-engine.md) |
| **E** | Enquiry & Counselling Pipeline | EC-001 to EC-019 | 🔴 Not Started | TBD |
| **F** | WhatsApp, IVR, Communication Engine | LC-007, LC-010, CC-001 to CC-023 | 🔴 Not Started | TBD |
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

## Group E — Planned 📋

**Theme:** Enquiry & Counselling (BRD Section 8.3)

| Req ID | Feature |
|--------|---------|
| EC-001 | Counsellor assignment — auto (round-robin/load) + manual |
| EC-002 | Lead status pipeline transitions with validation |
| EC-003 | Appointment booking (counselling sessions) |
| EC-004 | 360° lead view — complete activity timeline (comms, tasks, notes, payments) |
| EC-005 to EC-019 | Full counselling workflow, BANT qualification, notes, scheduling |

---

## Group F — Planned 📋

**Theme:** Communication Engine (BRD Section 8.5) + WhatsApp/IVR Lead Capture

| Req ID | Feature |
|--------|---------|
| LC-007 | WhatsApp Click-to-Chat auto-lead creation |
| LC-010 | IVR inbound call auto-lead |
| CC-001 to CC-023 | Email, SMS, WhatsApp, Voice, unified inbox, templates, DLT |

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
| LC-007 | Must Have | F | 🔴 |
| LC-008 | Must Have | C | ✅ |
| LC-009 | Must Have | B | ✅ |
| LC-010 | Must Have | F | 🔴 |
| LC-011 | Must Have | A | ✅ |
| LC-012 | Must Have | C | ✅ |
| LC-014 | Must Have | A | ✅ |
| LC-015 | Must Have | A+B | ✅ Complete |
| LC-018 | Must Have | A | ✅ |
| LC-019 | Must Have | G | 🔴 |
| LC-020 | Must Have | G | 🔴 |

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
