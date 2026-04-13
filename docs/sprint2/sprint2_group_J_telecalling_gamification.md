# Group J — Telecalling & Gamification

## 🎯 Objective
Deliver power/auto-dialler, call scripts, supervisor call monitoring, counsellor gamification, business card OCR, mobile offline mode, and biometric authentication, building on Telephony/IVR (Sprint 1, Group F) and Counselling foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| TC-001 | Power/auto-dialler | Should Have | ✅ Completed |
| TC-002 | Call scripts with branching | Should Have | ✅ Completed |
| TC-003 | Configurable call dispositions | Must Have | ✅ Completed |
| TC-004 | Post-call follow-up scheduling prompt | Must Have | ✅ Completed |
| TC-005 | Supervisor call monitoring | Should Have | ✅ Completed |
| TC-006 | Calling campaign management | Must Have | ✅ Completed |
| TC-007 | Call centre performance dashboard | Must Have | ✅ Completed |
| TC-008 | Automatic call recording, storage, playback and search | Must Have | ✅ Completed |
| TC-009 | Do-Not-Call (DNC) list management | Must Have | ✅ Completed |
| EC-010 | Counsellor performance gamification | Should Have | ⏳ |
| MB-004 | Business card scanner (OCR) | Should Have | ⏳ |
| MB-006 | Mobile offline mode | Should Have | ⏳ |
| MB-007 | Biometric authentication | Should Have | ⏳ |

## 🧩 Features Breakdown

### Feature: Power/Auto-Dialler (TC-001)
#### 📌 Description
Automated dialler for outbound calling campaigns, integrated with CRM lead lists.
#### 👤 User Stories
- As a counsellor, I can auto-dial leads from a campaign list.
#### ✅ Acceptance Criteria
- Given a campaign, when dialler is started, then calls are auto-placed and logged.
#### ⚙️ Backend Design
- Controllers: DiallerController
- Services: DiallerService
- Jobs: DiallerJob (queue: crm-telecalling)
- Events: CallPlacedEvent, CallCompletedEvent
- DB Schema: dialler_sessions, dialler_logs
#### 🎨 UI/UX
- dialler.blade.php (call queue, status)
#### 🔗 Dependencies
- Telephony/IVR (F), Lead foundation (A)
#### 🔐 Security / DPDP
- Call consent, no PII in logs
#### 🧪 Test Cases
- Dialler flow, call logging, DPDP consent

#### ✅ Implementation Completed (April 2026)
- Migrations: `create_dialler_sessions_table`, `create_dialler_logs_table`
- Models: `DiallerSession`, `DiallerLog`
- Enums: `DiallerSessionStatus`, `DiallerLogStatus`
- Service: `app/Services/CRM/Communication/DiallerService.php`
- Job: `app/Jobs/CRM/Communication/DiallerJob.php` (queue: `crm-telecalling`)
- Listener-driven progression: `ContinueDiallerOnCallLogged` advances queue after call completion
- Event coverage: `CallPlacedEvent` + existing `CallCompletedEvent` + `CallLoggedEvent`
- Web Controller + Routes: `DiallerWebController`, `crm.communication.voice.dialler.*`
- API Controller + Routes: `DiallerController`, `/api/v1/crm/dialler/sessions*`
- UI: `resources/views/crm/communication/voice/dialler.blade.php`
- Telephony integration: uses existing `VoiceService::initiateClickToCall()` and call logging pipeline

---

### Feature: Call Scripts with Branching (TC-002)
#### 📌 Description
Configurable call scripts with dynamic branching based on responses.
#### 👤 User Stories
- As a counsellor, I follow guided scripts during calls.
#### ✅ Acceptance Criteria
- Given a call, when script is active, then prompts and branches are shown per responses.
#### ⚙️ Backend Design
- Controllers: CallScriptController
- Models: CallScript
- DB Schema: call_scripts, call_script_steps
#### 🎨 UI/UX
- call-script.blade.php (script flow)
#### 🔗 Dependencies
- Telephony/IVR (F)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Script creation, branching logic

#### ✅ Implementation Completed (April 2026)
- Migrations: `create_call_scripts_table`, `create_call_script_steps_table`
- Models: `CallScript`, `CallScriptStep`
- Enums: `CallScriptStatus`, `CallScriptResponseType`
- Repository: `CallScriptRepositoryInterface`, `EloquentCallScriptRepository` (bound in `CrmCommunicationServiceProvider`)
- Service: `app/Services/CRM/Communication/CallScriptService.php` (supports `equals`, `contains`, `gte`, `lte`, `truthy` operators + default-next fallback)
- Requests: `StoreCallScriptRequest`, `ResolveCallScriptBranchRequest`
- Resources: `CallScriptResource`, `CallScriptStepResource`
- Web Controller + Routes: `CallScriptWebController`, `crm.communication.voice.scripts.*`
- API Controller + Routes: `CallScriptController`, `/api/v1/crm/voice/call-scripts*`
- UI: `resources/views/crm/communication/voice/call-script.blade.php` with script list, creation form, step table, and branch runner simulation
- Tests: `CallScriptApiTest`, `CallScriptCommunicationTest` (CRUD + branch resolution + archive)

---

### Feature: Supervisor Call Monitoring (TC-005)
#### 📌 Description
Supervisors can listen, whisper, or barge-in on live calls for QA/training.
#### 👤 User Stories
- As a supervisor, I monitor and coach live calls.
#### ✅ Acceptance Criteria
- Given a live call, when supervisor joins, then listen/whisper/barge-in is available.
#### ⚙️ Backend Design
- Services: CallMonitorService
- DB Schema: call_monitor_logs
#### 🎨 UI/UX
- call-monitor.blade.php (monitor panel)
#### 🔗 Dependencies
- Telephony/IVR (F)
#### 🔐 Security / DPDP
- Consent, audit log
#### 🧪 Test Cases
- Monitor, whisper, barge-in

#### ✅ Implementation Completed (April 2026)
- Migration: `create_call_monitor_logs_table`
- Enums: `CallMonitorMode`, `CallMonitorStatus`
- Model: `CallMonitorLog`
- Repository: `CallMonitorRepositoryInterface`, `EloquentCallMonitorRepository`
- Service: `app/Services/CRM/Communication/CallMonitorService.php`
- Requests: `StoreCallMonitorRequest`, `StopCallMonitorRequest`
- Resource: `CallMonitorLogResource`
- Web Controller + Routes: `CallMonitorWebController`, `crm.communication.voice.monitor.*`
- API Controller + Routes: `CallMonitorController`, `/api/v1/crm/voice/call-monitor/sessions*`
- UI: `resources/views/crm/communication/voice/call-monitor.blade.php`
- Consent guard: monitoring blocked when `call_consent_given=false` (DPDP compliance)
- Tests: `CallMonitorApiTest`, `CallMonitorCommunicationTest`

---

### Feature: Configurable Call Dispositions (TC-003)
#### 📌 Description
Institution-specific configuration for call outcomes used by counsellors after each call.
#### 👤 User Stories
- As an admin, I can configure active call dispositions and labels for my institution.
#### ✅ Acceptance Criteria
- Given call outcome settings, when a counsellor records disposition, then only active configured options are available.
#### ⚙️ Backend Design
- Services: CallDispositionService
- DB Schema: call_disposition_configs
#### 🎨 UI/UX
- dispositions.blade.php (settings page)
#### 🔗 Dependencies
- Telephony/IVR (F), Call Log (F4)
#### 🔐 Security / DPDP
- Institution-scoped config; no PII in configuration data
#### 🧪 Test Cases
- Config list/update, API config update

#### ✅ Implementation Completed (April 2026)
- Migration: `create_call_disposition_configs_table`
- Model: `CallDispositionConfig`
- Repository: `CallDispositionRepositoryInterface`, `EloquentCallDispositionRepository`
- Service: `app/Services/CRM/Communication/CallDispositionService.php`
- Requests/Resource: `StoreCallDispositionConfigRequest`, `CallDispositionConfigResource`
- Web Controller + Routes: `CallDispositionWebController`, `crm.communication.voice.dispositions.*`
- API Controller + Routes: `CallDispositionController`, `/api/v1/crm/voice/call-dispositions*`
- UI: `resources/views/crm/communication/voice/dispositions.blade.php`
- Voice log integration: `CallLogWebController` validates dispositions against active configs
- Tests: `CallDispositionApiTest`, `CallDispositionFollowUpTest`

---

### Feature: Post-Call Follow-up Prompt (TC-004)
#### 📌 Description
After specific call outcomes, the system immediately prompts counsellors to schedule the next follow-up session.
#### 👤 User Stories
- As a counsellor, I am prompted to book the next follow-up when call outcomes indicate callback/follow-up requirement.
#### ✅ Acceptance Criteria
- Given a follow-up-required disposition, when the call is finalised, then counsellor is redirected to follow-up scheduling.
#### ⚙️ Backend Design
- Service lookup: `CallDispositionService::shouldPromptFollowUp`
- Controller flow: `CallLogWebController::updateDisposition`
#### 🎨 UI/UX
- sessions/create.blade.php (follow-up scheduling form with post-call prompt banner)
#### 🔗 Dependencies
- Counselling Sessions (EC-015), Call dispositions (TC-003)
#### 🔐 Security / DPDP
- Prompt only for authenticated counsellors with `crm.sessions.create`
#### 🧪 Test Cases
- Redirect after callback disposition, prompt banner rendering

#### ✅ Implementation Completed (April 2026)
- Follow-up trigger wired to disposition config flag `requires_follow_up`
- Automatic redirect from call disposition save to `crm.leads.sessions.create`
- Session prompt payload flashed as `follow_up_prompt`
- Added session create view: `resources/views/crm/sessions/create.blade.php`
- Updated session create controller context with counsellor listing and prompt context
- Tests: `CallDispositionFollowUpTest`, `FollowUpSessionPromptViewTest`

---

### Feature: Do-Not-Call (DNC) List Management (TC-009)
#### 📌 Description
Institution-scoped DNC list that permanently blocks all outbound communication (calls, SMS, email) to a lead until explicitly removed by an authorised manager. DPDP-compliant: opt-out flag is preserved even after DNC removal; re-consent must be collected before communication resumes.
#### 👤 User Stories
- As a manager, I can add a lead to the DNC list with a mandatory reason so that no further contact is made.
- As a manager, I can view the full DNC list and search by name or reason.
- As a manager, I can remove a lead from DNC for re-engagement, with the understanding that opt-out remains until new consent is captured.
#### ✅ Acceptance Criteria
- Given a lead with consent, when added to DNC with a reason, then `dnc_at` and `opt_out` are set immediately.
- Given a lead already on DNC, when `addToDnc` is called again, then the original `dnc_at` is not overwritten (idempotent).
- Given a DNC lead, when removed from the list, then `dnc_at`/`dnc_reason` are cleared but `opt_out` is preserved (DPDP).
- Given the DNC list page, when searched, then only matching institution-scoped DNC leads are shown.
- Given a user without `crm.dnc.manage`, when accessing DNC routes, then a 403 is returned.
#### ⚙️ Backend Design
- Service: `DncService` (`addToDnc`, `removeFromDnc`, `paginateDncLeads`)
- Controller: `DncWebController` (`index`, `store`, `destroy`)
- FormRequest: `StoreDncEntryRequest` (reason required, max 255)
- No new migration required — uses existing `leads.dnc_at`, `leads.dnc_reason`, `leads.opt_out` columns
#### 🎨 UI/UX
- `resources/views/crm/communication/voice/dnc/index.blade.php`
	- Search filter (name / reason)
	- Table: lead name (linked), DNC reason, assigned counsellor, added timestamp, remove action with Alpine.js confirm popover
	- DPDP compliance notice banner
	- Empty state with icon
- `resources/views/crm/leads/_partials/sidebar.blade.php`
	- New "Do-Not-Call (DNC)" card added to lead sidebar
	- Shows DNC status badge + reason if on DNC; "Remove from DNC" button
	- Shows Alpine.js inline "Add to DNC" form with reason field when not on DNC
	- Link to full DNC list
- `resources/views/crm/communication/voice/index.blade.php`
	- "DNC List" navigation button added to Call Log header
#### 🔗 Dependencies
- Lead model (existing `dnc_at`, `dnc_reason`, `opt_out` fields)
- Communication services: DNC check already exists in `SmsService`, `EmailService`, `DiallerService`, `VoiceService`, `TelecallingCampaignService`
#### 🔐 Security / DPDP
- Permission: `crm.dnc.manage` (granted to admissions-director, admissions-manager)
- opt_out is never cleared on DNC removal — DPDP right-to-opt-out preserved
- Mobile number is NOT displayed in DNC list (encrypted PII, shown as "Hidden")
- Institution scope enforced by `InstitutionScope` global scope on `Lead` model
#### 🧪 Test Cases
- Service: addToDnc sets flags, idempotent, removeFromDnc preserves opt_out, pagination returns only DNC leads
- Web: index page accessible with permission, blocked without; store adds lead; validation rejects empty reason; destroy removes from DNC; unauthorised store returns 403

#### ✅ Implementation Completed (April 2026)
- Service: `app/Services/CRM/Communication/DncService.php`
	- `addToDnc($lead, $reason)` — idempotent, sets `dnc_at`, `dnc_reason`, `opt_out`
	- `removeFromDnc($lead)` — clears `dnc_at`/`dnc_reason`, preserves `opt_out` (DPDP)
	- `paginateDncLeads($institutionId, $search, $perPage)` — scoped, searchable, paginated
- Web Request: `app/Http/Requests/CRM/StoreDncEntryRequest.php`
	- reason: required, string, max:255
- Web Controller: `app/Http/Controllers/Web/CRM/DncWebController.php`
	- `index()` — institution-scoped DNC list with search
	- `store(Lead $lead)` — adds lead to DNC
	- `destroy(Lead $lead)` — removes lead from DNC
- Route wiring (`routes/web.php` inside `crm.communication.voice.*` prefix):
	- `GET crm/communication/voice/dnc` → `crm.communication.voice.dnc.index`
	- `POST crm/communication/voice/dnc/{lead:uuid}` → `crm.communication.voice.dnc.store`
	- `DELETE crm/communication/voice/dnc/{lead:uuid}` → `crm.communication.voice.dnc.destroy`
- Blade Views:
	- `resources/views/crm/communication/voice/dnc/index.blade.php` — full DNC management screen
	- `resources/views/crm/leads/_partials/sidebar.blade.php` — DNC card added to lead sidebar
	- `resources/views/crm/communication/voice/index.blade.php` — DNC List nav button
- Permission seeder: `crm.dnc.manage` added to `PermissionSeeder::permissions()`
- Role seeder: `crm.dnc.manage` granted to `admissions-director` and `admissions-manager`
- Tests: `tests/Feature/CRM/Communication/DncManagementTc009Test.php`
	- 10 tests passed, 25 assertions
	- TC-007 + TC-008 regression: 6 tests passed, 28 assertions

---

### Feature: Counsellor Performance Gamification (EC-010)
#### 📌 Description
Gamified dashboard for counsellor KPIs, leaderboards, badges, and rewards.
#### 👤 User Stories
- As a counsellor, I see my rank and earn rewards for performance.
#### ✅ Acceptance Criteria
- Given activity, when KPIs are met, then badges/leaderboard update.
#### ⚙️ Backend Design
- Services: GamificationService
- DB Schema: gamification_scores, badges, leaderboards
#### 🎨 UI/UX
- gamification-dashboard.blade.php (leaderboard, badges)
#### 🔗 Dependencies
- Lead activity, Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Score calculation, badge assignment

---

### Feature: Calling Campaign Management (TC-006)
#### 📌 Description
Campaign management module to define callable lead lists, assign agents, configure campaign time windows, launch dialler sessions, and track campaign progress.
#### 👤 User Stories
- As a campaign manager, I can create and launch telecalling campaigns with selected agents and leads.
- As an operations lead, I can track campaign progress from dialler session outcomes.
#### ✅ Acceptance Criteria
- Given a campaign with assigned agents and leads, when launched within time window, then dialler sessions are created and progress metrics update.
#### ⚙️ Backend Design
- Services: `TelecallingCampaignService`
- Repository: `TelecallingCampaignRepositoryInterface`, `EloquentTelecallingCampaignRepository`
- DB Schema: `telecalling_campaigns`, `telecalling_campaign_agents`, `telecalling_campaign_leads`
- Dialler integration: `dialler_sessions.telecalling_campaign_id`
#### 🎨 UI/UX
- `campaigns.blade.php` (campaign creation, assignment, launch, and progress list)
- `campaigns-edit.blade.php` (dedicated campaign edit screen)
#### 🔗 Dependencies
- Dialler engine (TC-001), call log stack (F4), lead and user master data
#### 🔐 Security / DPDP
- Institution-scoped assignment validation
- Callable-lead checks enforce consent, DNC, and opt-out constraints
#### 🧪 Test Cases
- Web: campaign create + launch flow
- API: campaign create/list/launch envelope and progress payload

#### ✅ Implementation Completed (April 2026)
- Migrations:
	- `create_telecalling_campaigns_table`
	- `create_telecalling_campaign_agents_table`
	- `create_telecalling_campaign_leads_table`
	- `add_telecalling_campaign_to_dialler_sessions_table`
- Enum: `TelecallingCampaignStatus`
- Models: `TelecallingCampaign`, `TelecallingCampaignAgent`, `TelecallingCampaignLead`
- Repository + binding: `TelecallingCampaignRepositoryInterface`, `EloquentTelecallingCampaignRepository`, binding in `CrmCommunicationServiceProvider`
- Service: `app/Services/CRM/Communication/TelecallingCampaignService.php`
- Web Request/Controller/View:
	- `StoreTelecallingCampaignRequest`
	- `TelecallingCampaignWebController`
	- `resources/views/crm/communication/voice/campaigns.blade.php`
	- `resources/views/crm/communication/voice/campaigns-edit.blade.php`
- API Controller/Resource:
	- `TelecallingCampaignController`
	- `TelecallingCampaignResource`
	- routes under `/api/v1/crm/voice/campaigns*`
- Route wiring:
	- web: `crm.communication.voice.campaigns.*`
	- api: `api.crm.voice.campaigns.*`
- Tests:
	- `TelecallingCampaignWebTest`
	- `TelecallingCampaignApiTest`

---

### Feature: Call Centre Performance Dashboard (TC-007)
#### 📌 Description
Real-time dashboard displaying agent performance metrics: calls made, talk time, connect rate, conversions per agent.
#### 👤 User Stories
- As a call centre manager, I view agent performance metrics for optimization and coaching.
#### ✅ Acceptance Criteria
- Given call logs, when dashboard is loaded, then calls made, talk time, connects, and conversions are displayed per agent.
#### ⚙️ Backend Design
- Service: `CallCentrePerformanceService.php` (calculates per-agent metrics from CallLog and Lead status)
- Controllers: `CallCentrePerformanceWebController`, `CallCentrePerformanceController` (API)
- DB Schema: Reads from `call_logs` (outbound calls, status=COMPLETED for connects) and `leads` (assigned_counsellor_id + status=ENROLLED for conversions)
#### 🎨 UI/UX
- `performance.blade.php` (agent performance table, Chart.js bar/line charts for call volume and daily trends)
- 4 summary metric cards (total calls, connect rate, avg talk time, conversion rate)
- Bar chart: calls made vs connects by agent
- Line chart: daily call volume trend
- Responsive Tailwind grid, color-coded thresholds for connect/conversion rates
#### 🔗 Dependencies
- CallLog (F4), Lead master data, user roles
#### 🔐 Security / DPDP
- Permission: `crm.voice.performance`
- Institution-scoped query filters
- No PII in performance aggregations
#### 🧪 Test Cases
- Web: dashboard loads with date filter, displays summary/agent breakdown
- API: returns JSON performance report with summary + per_agent + volume_trend arrays
- Institution scoping: only shows call logs for current institution

#### ✅ Implementation Completed (April 2026)
- Service: `app/Services/CRM/Communication/CallCentrePerformanceService.php`
	- `buildPerformanceReport()`: aggregates calls made, connects, talk time, conversions per agent
	- `calculateConversionsPerAgent()`: joins CallLog → Lead status transitions
	- `getDailyCallVolumeTrend()`: daily aggregation for line chart
- Web Controller: `app/Http/Controllers/Web/CRM/CallCentrePerformanceWebController.php`
	- `index()`: returns Blade view with report + volumeTrend
	- Query params: `from_date`, `to_date`, `agent_id` (optional)
- API Controller: `app/Http/Controllers/Api/CRM/CallCentrePerformanceController.php`
	- `performance()`: returns JSON envelope with report + volume_trend
	- Validates date range: `from_date`, `to_date`, optional `agent_id`
- Blade View: `resources/views/crm/communication/voice/performance.blade.php`
	- Summary cards: total calls (blue), connect rate (emerald), avg talk time (violet), conversion rate (amber)
	- Agent performance table: calls, connects, connect%, avg talk, total talk, conversions, conv%
	- Chart.js: bar chart (calls made + connects by agent), line chart (daily call volume)
- Route wiring:
	- web: `GET crm/communication/voice/performance` → `crm.communication.voice.performance`
	- api: `GET api/v1/crm/voice/performance` → `api.v1.crm.voice.performance`
- Permission: `crm.voice.performance` added to `PermissionSeeder`
- Tests:
	- `tests/Feature/CRM/Communication/CallCentrePerformanceTest.php`
	- 3 tests: displays dashboard, institution scoping, API endpoint JSON
	- 21 assertions passed (summary metrics, per-agent breakdown, API response structure)

---

### Feature: Automatic Call Recording, Storage, Playback and Search (TC-008)
#### 📌 Description
Automatic capture of telephony recording links for consented calls, searchable recording-aware call logs, and playback access from CRM.
#### 👤 User Stories
- As a counsellor/supervisor, I can play a call recording directly from call logs when consent exists.
- As an operations manager, I can filter call logs by recording availability and quickly locate recorded calls.
#### ✅ Acceptance Criteria
- Given telephony webhook payload includes recording URL, when call consent is true, then recording URL is stored automatically.
- Given call consent is false, when telephony webhook contains recording URL, then recording URL is not stored.
- Given call logs page, when filtered by recording availability, then only matching records are shown.
- Given a consented call with recording URL, when play is clicked, then user is redirected to recording playback URL.
#### ⚙️ Backend Design
- `VoiceService::initiateClickToCall()` now propagates lead `call_consent_given` into call logs.
- `ProcessTelephonyWebhookJob` now parses recording URL fields (`recording_url`, `recording_file`, `RecordingUrl`) and stores URL only when consent is true.
- `CallLogWebController` extended with recording-aware search filters and consent-gated playback action.
#### 🎨 UI/UX
- `resources/views/crm/communication/voice/index.blade.php`
	- Added filter bar: search text, from date, to date, with/without recording selector.
	- Added recording column in call log table with `Play` action for consented calls.
	- Shows `No consent` / `Pending` states where playback is not allowed/available.
#### 🔗 Dependencies
- Call log stack (F4), telephony webhook ingestion, lead consent flags.
#### 🔐 Security / DPDP
- Strict consent gate before storing or playing recordings.
- No recording persistence when `call_consent_given=false`.
- Existing institution scope enforced by model global scope.
#### 🧪 Test Cases
- Webhook recording persistence with consent true.
- Webhook recording suppression with consent false.
- Recording playback redirect for consented call logs.

#### ✅ Implementation Completed (April 2026)
- Updated: `app/Services/CRM/Communication/VoiceService.php`
	- click-to-call now writes `call_consent_given` onto `call_logs`
- Updated: `app/Jobs/CRM/Communication/ProcessTelephonyWebhookJob.php`
	- automatic recording URL persistence with DPDP consent guard
- Updated: `app/Http/Controllers/Web/CRM/CallLogWebController.php`
	- search/filter support (`search`, `from_date`, `to_date`, `has_recording`)
	- playback action `playRecording()` with consent + URL checks
- Updated: `resources/views/crm/communication/voice/index.blade.php`
	- recording filter form + recording playback column
- Updated route wiring:
	- web: `GET crm/communication/voice/calls/{callLog:uuid}/recording` → `crm.communication.voice.calls.recording`
- Tests:
	- `tests/Feature/CRM/Communication/CallRecordingTc008Test.php`
	- 3 tests passed (7 assertions)

---

### Feature: Business Card Scanner (OCR) (MB-004)
#### 📌 Description
Mobile OCR to scan business cards and auto-create leads.
#### 👤 User Stories
- As a counsellor, I scan a card and a lead is created.
#### ✅ Acceptance Criteria
- Given a card scan, when OCR is successful, then lead is created with extracted data.
#### ⚙️ Backend Design
- Services: OcrService
- DB Schema: ocr_uploads
#### 🎨 UI/UX
- ocr-upload.blade.php (mobile UI)
#### 🔗 Dependencies
- Mobile app, Lead foundation (A)
#### 🔐 Security / DPDP
- Consent, no PII in logs
#### 🧪 Test Cases
- OCR accuracy, lead creation

---

### Feature: Mobile Offline Mode (MB-006)
#### 📌 Description
Allow mobile users to view, add notes, and scan cards offline, with sync on reconnect.
#### 👤 User Stories
- As a counsellor, I work offline and sync later.
#### ✅ Acceptance Criteria
- Given offline mode, when reconnected, then data syncs to CRM.
#### ⚙️ Backend Design
- Mobile app update, sync logic
#### 🎨 UI/UX
- Mobile UI (offline indicators)
#### 🔗 Dependencies
- Mobile app
#### 🔐 Security / DPDP
- Local encryption, DPDP for sync
#### 🧪 Test Cases
- Offline actions, sync

---

### Feature: Biometric Authentication (MB-007)
#### 📌 Description
Enable fingerprint/face unlock for mobile app access.
#### 👤 User Stories
- As a counsellor, I log in with biometrics.
#### ✅ Acceptance Criteria
- Given biometric setup, when enabled, then login uses fingerprint/face.
#### ⚙️ Backend Design
- Mobile app update, auth logic
#### 🎨 UI/UX
- Mobile UI (biometric prompt)
#### 🔗 Dependencies
- Mobile app
#### 🔐 Security / DPDP
- Biometric data never leaves device
#### 🧪 Test Cases
- Biometric login, fallback

---
