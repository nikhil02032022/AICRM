# Group H — Marketing Automation & Attribution

## 🎯 Objective
Deliver advanced marketing automation, multi-touch attribution, kiosk/chat lead capture, and cost-per-lead tracking for A2A-CRM, building on the Communication Engine (Sprint 1, Group F) and Lead Capture foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| LC-005 | Landing page builder for lead capture | Should Have | ✅ Implemented (done) |
| LC-006 | Website chatbot for lead capture | Should Have | ✅ Implemented (done) |
| LC-013 | Walk-in enquiry kiosk interface | Should Have | ✅ Implemented (done) |
| LC-016 | Multi-touch attribution model | Should Have | ✅ Implemented (done) |
| LC-017 | Cost-per-lead tracking | Should Have | ✅ Implemented (done) |
| MA-001–MA-010 | Visual workflow builder, triggers, actions, A/B testing, drip, re-engagement, reporting | Must/Should Have | ✅ Implemented (MA-001 to MA-010) |

## 📊 Current Build Summary
- Fully implemented: LC-005, LC-006, LC-013, LC-016, LC-017 (done)
- Implemented in current slice: MA-001, MA-002, MA-003, MA-004, MA-005, MA-006, MA-007, MA-008, MA-009, MA-010
- Remaining: None

## 🧩 Features Breakdown

### Feature: Landing Page Builder (LC-005)
#### 📌 Description
Institutions can create custom landing pages for campaigns, mapped to lead capture and attribution.
#### 🛠️ Implementation Log
- Added `landing_pages` migration and `LandingPage` model with UUIDs, soft deletes, tenant scoping, publish state, SEO, content blocks, and attribution parameters.
- Added repository, DTO, service, event, policy, API resource, API controller, CRM web controller, and public controller for published landing page rendering.
- Wired authenticated CRM routes under `/crm/marketing/landing-pages`, integration API routes under `/api/v1/crm/landing-pages`, and public published-page routes under `/lp/{slug}`.
- Added CRM Blade management screens plus a public-facing landing page template that embeds the existing CRM web form flow instead of duplicating lead-capture logic.
- Added initial API and public tests covering create/update/delete, institution isolation, published visibility, and draft 404 behaviour.
- Added ordered content-block persistence (`id`, `type`, `order`) plus builder payload normalization for richer landing-page composition.
- Added interactive value-section builder controls in the CRM edit screen (reorder, add/remove cards) and quick-apply campaign template presets for faster page setup.
- Added API test coverage to validate ordered block payload storage for landing pages.
- Added pointer-based drag-and-drop block reordering in the landing-page editor and stable order rendering on the public page.
- Added request-level `content_json` parsing support so builder state can persist block structures through standard form submissions.
- Added API test coverage for `content_json` payload ingestion and ordered block persistence.
- Added `landing_page_views` event tracking with UTM capture on public page visits for campaign-level view analytics.
- Added richer block composition support (`value_card`, `stat`, `faq`) across requests, DTO/service normalization, editor UX, and public rendering.
- Added keyboard-accessible reordering in the editor (`Alt + Up/Down`) alongside pointer drag/drop, with focusable blocks and ARIA group semantics.
- Added campaign analytics surfacing in CRM and API (`view_count`, `view_count_last_7d`) for deeper landing-page performance visibility.
#### ✅ Delivered Scope In This Slice
- Campaign landing page CRUD for CRM staff
- Public published landing page rendering
- Reuse of existing DPDP-compliant web form embed flow for lead capture
- Attribution parameter propagation to the embedded form URL
- Public view-event capture with UTM metadata for ROI reporting inputs
#### 🔎 Code Evidence
- `app/Models/CRM/LandingPage.php` (`formEmbedUrl()` appends attribution query params)
- `app/Http/Controllers/Web/CRM/LandingPageWebController.php`
- `app/Http/Controllers/Api/CRM/LandingPageController.php`
- `app/Http/Controllers/Public/PublicLandingPageController.php`
- `resources/views/crm/marketing/landing-pages/index.blade.php`
- `resources/views/crm/marketing/landing-pages/edit.blade.php`
- `resources/views/public/landing-page/show.blade.php`
- `tests/Feature/CRM/Api/LandingPageApiTest.php`
- `tests/Feature/CRM/Public/PublicLandingPageTest.php`
#### ⏭️ Remaining For Full BRD Completion
- None for LC-005 in the current Group H completion scope.
#### 👤 User Stories
- As a marketing manager, I can create and publish landing pages to capture leads.
#### ✅ Acceptance Criteria
- Given a campaign, when a landing page is created, then leads are attributed to the campaign and source.
#### ⚙️ Backend Design
- Controllers: LandingPageController (Web)
- Services: LandingPageService
- DTOs: CreateLandingPageDTO
- Jobs: N/A
- Events: LandingPageCreatedEvent
- DB Schema: landing_pages (uuid, institution_id, title, content, attribution_params, created_by, ...)
#### 🎨 UI/UX
- `resources/views/crm/marketing/landing-pages/edit.blade.php` (create/edit and publish workflow)
- `resources/views/crm/marketing/landing-pages/index.blade.php` (campaign page list and actions)
- `resources/views/public/landing-page/show.blade.php` (public campaign page with embedded CRM form)
#### 🔗 Dependencies
- Lead foundation (A), Communication Engine (F)
#### 🔐 Security / DPDP
- Consent capture, no PII in logs
#### 🧪 Test Cases
- Create, edit, publish, attribution, DPDP compliance

#### ✅ Quick Manual UI Walkthrough Checklist (LC-005)
- [ ] Login with `crm.campaigns.manage` permission.
- [ ] Open `/crm/marketing/landing-pages` and verify list, filters, and status chips.
- [ ] Click **New Landing Page** and fill identity, hero, CTA, UTM, linked form, and SEO fields.
- [ ] In builder, add mixed block types (`value_card`, `stat`, `faq`) and save.
- [ ] Reorder blocks using drag/drop and keyboard (`Alt + Up/Down`) and save again.
- [ ] Publish page and open public URL from CRM action.
- [ ] Validate public rendering of all block types and embedded form visibility.
- [ ] Validate UTM propagation in embedded form URL.
- [ ] Hit public URL with UTM query once/twice and verify CRM view metrics update (all-time + last 7 days).
- [ ] Set page to draft/archived and confirm public URL returns 404.
- [ ] Delete page and confirm it disappears from active list.

---

### Feature: Website Chatbot (LC-006)
#### 📌 Description
Embeddable website chatbot for institution websites that captures lead details and one query, then auto-creates CRM leads for staff follow-up.
#### 🛠️ Implementation Log
- Added `chat_leads` migration and `ChatLead` model with UUIDs, soft deletes, tenant scoping, encrypted transcript storage, attribution parameters, and DPDP consent fields.
- Added `CreateChatLeadDTO`, marketing repository + service layer, `ChatLeadCreatedEvent`, and `ProcessChatLeadJob` to process captured chatbot enquiries asynchronously.
- Implemented public chatbot endpoints: `GET /chat/widget/{institution:uuid}` and `POST /chat/widget/{institution:uuid}/submit` to capture one query + lead details + consent and create CRM leads.
- Implemented CRM web management screen under `/crm/marketing/chat-widget` with embed snippet, preview link, captured lead list, and transcript modal for admin review.
- Implemented integration API endpoints under `/api/v1/crm/chat-widget/leads` for authenticated external ingestion and retrieval.
- Refined the public UI from a multi-message chat look into a capture-first website chatbot flow: one query field, basic details, and admin-side review/contact from CRM.
- Added live-agent response workflow with transcript append endpoints and CRM actions to post staff replies per captured chat session.
- Added handoff lifecycle controls (`captured`, `pending_agent`, `live_agent`, `resolved`) with optional assignee mapping and status updates from CRM/API.
- Added chat SLA analytics fields (first response timestamp, last message timestamp, inbound/outbound counts) plus dashboard metrics for pending/live/resolved and avg first-response minutes.
#### ✅ Delivered Scope In This Slice
- Embeddable website chatbot UI for institution websites (iframe-based).
- Lead capture with first name, last name, mobile, optional email, one query message, UTM parameters, and DPDP consent.
- Automatic CRM lead creation from chatbot submissions (`source=live_chat`).
- Admin review screen with captured enquiry list, transcript modal, lead follow-up handoff controls, and staff reply actions.
- API support for staff replies and handoff status changes for external integration consumers.
#### 🔎 Code Evidence
- `app/Models/CRM/ChatLead.php`
- `app/Services/CRM/Marketing/ChatWidgetService.php`
- `app/Http/Controllers/Public/PublicChatWidgetController.php`
- `app/Http/Controllers/Web/CRM/ChatWidgetWebController.php`
- `app/Http/Controllers/Api/CRM/ChatWidgetController.php`
- `app/Http/Requests/Api/CRM/StoreChatLeadAgentReplyRequest.php`
- `app/Http/Requests/Api/CRM/UpdateChatLeadHandoffRequest.php`
- `app/DTOs/CRM/CreateChatLeadDTO.php`
- `app/Jobs/CRM/ProcessChatLeadJob.php`
- `app/Events/CRM/ChatLeadCreatedEvent.php`
- `resources/views/public/chat-widget/show.blade.php`
- `resources/views/crm/marketing/chat-widget/index.blade.php`
- `database/migrations/2026_04_22_000001_create_chat_leads_table.php`
- `database/migrations/2026_04_24_000002_add_handoff_and_sla_to_chat_leads_table.php`
- `tests/Feature/CRM/Public/PublicChatWidgetSubmissionTest.php`
- `tests/Feature/CRM/Api/ChatWidgetApiTest.php`
#### ⏭️ Pending For Full BRD Completion
- Real-time two-way live agent chat is not implemented.
- Real-time socket/WebRTC session streaming is not implemented; current flow is staff reply append + status workflow.
- Campaign-level chatbot effectiveness attribution dashboards remain pending.
#### 👤 User Stories
- As a prospective student, I can share my details and one query through a website chatbot and become a lead.
#### ✅ Acceptance Criteria
- Given a chatbot enquiry, when a user submits details and a query, then a lead is created and attributed.
#### ⚙️ Backend Design
- Controllers: ChatWidgetController (Web, API)
- Services: ChatWidgetService
- DTOs: CreateChatLeadDTO
- Jobs: ProcessChatLeadJob
- Events: ChatLeadCreatedEvent
- DB Schema: chat_leads (uuid, institution_id, session, transcript, attribution, ...)
#### 🎨 UI/UX
- `resources/views/public/chat-widget/show.blade.php` (website chatbot lead-capture flow)
- `resources/views/crm/marketing/chat-widget/index.blade.php` (embed management, transcript review, follow-up handoff)
#### 🔗 Dependencies
- Communication Engine (F), Lead foundation (A)
#### 🔐 Security / DPDP
- Consent prompt, no PII in logs
#### 🧪 Test Cases
- Chatbot capture flow, lead creation, API ingestion, DPDP consent, staff reply append, handoff status updates

---

### Feature: Walk-in Enquiry Kiosk (LC-013)
#### 📌 Description
Kiosk-friendly interface for walk-in lead capture at events/campus.
#### 🛠️ Implementation Log
- Added `CreateKioskLeadDTO`, `PublicKioskLeadSubmissionRequest`, `KioskService`, and `KioskLeadCreatedEvent` to implement dedicated kiosk lead ingestion without duplicating lead domain logic.
- Implemented public kiosk endpoints: `GET /kiosk/{institution:uuid}` and `POST /kiosk/{institution:uuid}/submit` for touch-friendly walk-in capture at events/campus desks.
- Implemented CRM web management screen under `/crm/marketing/kiosk` with launch URL, submit endpoint visibility, and recent kiosk-captured leads for operations monitoring.
- Reused `LeadService` with `PublicFormActor` so kiosk submissions remain tenant-scoped, DPDP consent-aware, and fully integrated with existing lead jobs/events.
- Added feature tests covering successful capture, consent enforcement, and inactive institution access blocking.
- Added CRM sidebar navigation entry for kiosk (`crm.marketing.kiosk.index`) and aligned role seeding so admissions-director and admissions-manager include `crm.campaigns.manage` for menu visibility/access.
- Verified on 2026-04-11 that `tests/Feature/CRM/Public/PublicKioskSubmissionTest.php` passes (3 tests, 8 assertions) in the current workspace.
#### ✅ Delivered Scope In This Slice
- Touch-friendly kiosk capture UI for first name, last name, mobile, optional email, one admission query, and consent.
- Dedicated walk-in kiosk lead creation flow mapped to lead `source=walk_in` with kiosk attribution markers.
- CRM admin screen to launch kiosk capture and monitor recent walk-in kiosk submissions.
#### 🔎 Code Evidence
- `app/DTOs/CRM/CreateKioskLeadDTO.php`
- `app/Http/Requests/Public/PublicKioskLeadSubmissionRequest.php`
- `app/Services/CRM/Marketing/KioskService.php`
- `app/Events/CRM/KioskLeadCreatedEvent.php`
- `app/Http/Controllers/Public/PublicKioskController.php`
- `app/Http/Controllers/Web/CRM/KioskWebController.php`
- `routes/web.php`
- `resources/views/public/kiosk/show.blade.php`
- `resources/views/crm/marketing/kiosk/index.blade.php`
- `tests/Feature/CRM/Public/PublicKioskSubmissionTest.php`
#### ⏭️ Pending For Full BRD Completion
- Dedicated kiosk device management (per-device auth/session tracking) is not implemented.
- Offline-first kiosk submission queueing for poor-connectivity events is not implemented.
- Kiosk analytics dashboard (event-wise conversion, staff throughput, queue times) remains pending.
#### 👤 User Stories
- As a staff member, I can register walk-in enquiries quickly.
#### ✅ Acceptance Criteria
- Given a walk-in, when details are entered, then a lead is created with source=walk_in and tagged for kiosk capture.
#### ⚙️ Backend Design
- Controllers: PublicKioskController, KioskWebController
- Services: KioskService
- DTOs: CreateKioskLeadDTO
- Jobs: N/A
- Events: KioskLeadCreatedEvent
- DB Schema: leads (source=walk_in, consent fields, source_utm_params with kiosk attribution markers)
#### 🎨 UI/UX
- `resources/views/public/kiosk/show.blade.php` (touch-friendly kiosk submission flow)
- `resources/views/crm/marketing/kiosk/index.blade.php` (kiosk launch and monitoring page)
#### 🔗 Dependencies
- Lead foundation (A)
#### 🔐 Security / DPDP
- Consent checkbox, audit log
#### 🧪 Test Cases
- Kiosk flow, DPDP consent

---

### Feature: Multi-Touch Attribution (LC-016)
#### 📌 Description
Track all touchpoints (first, last, linear, configurable) for each lead.
#### ✅ Implemented In Current Build
- Added `lead_attributions` migration and `LeadAttribution` model with UUIDs, tenant scoping, attribution metadata, touch types, and credit columns.
- Added attribution repository + service layer to record initial touchpoints and recalculate first-touch, last-touch, and linear credits on each new touchpoint.
- Registered `CaptureLeadAttributionOnCreate` listener on lead creation so first-touch attribution is captured automatically across lead ingestion paths.
- Added integration API endpoints for lead attribution timeline retrieval and manual touchpoint addition.
- Added CRM web attribution screen for searching lead timelines and appending offline/assisted touchpoints.
- Added feature tests validating first-touch capture and credit recomputation.
- Verified on 2026-04-11 that `tests/Feature/CRM/Api/AttributionAndCostTrackingApiTest.php` passes for attribution scenarios (3 tests total in suite, 23 assertions).
#### ⏭️ Pending For Full BRD Completion
- Configurable weighting strategies beyond first/last/linear (for example position-based and time-decay) are not yet implemented.
- Advanced attribution analytics dashboards and cohort visualisations remain pending.
#### 👤 User Stories
- As a marketing manager, I can view attribution reports for leads.
#### ✅ Acceptance Criteria
- Given a lead, when multiple sources are involved, then all are tracked and reportable.
#### ⚙️ Backend Design
- Services: AttributionService
- Models: LeadAttribution
- DB Schema: lead_attributions (lead_uuid, touch_type, source, timestamp)
#### 🎨 UI/UX
- Attribution UI in lead detail, reports
#### 🔗 Dependencies
- Lead foundation (A), Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs, audit trail
#### 🧪 Test Cases
- Attribution logic, reporting

---

### Feature: Cost-per-Lead Tracking (LC-017)
#### 📌 Description
Track campaign spend and calculate cost per lead by source.
#### ✅ Implemented In Current Build
- Added `campaign_spends` migration and `CampaignSpend` model with attribution-model-aware spend records.
- Added spend repository + `CostTrackingService` to compute attributed lead counts and cost-per-lead values per spend row.
- Added integration API endpoints for campaign spend creation and CPL report retrieval with filters.
- Added CRM web cost-tracking dashboard for spend entry, filtering, and CPL table review.
- Added feature tests validating spend-to-CPL calculation against attributed leads.
- Verified on 2026-04-11 that `tests/Feature/CRM/Api/AttributionAndCostTrackingApiTest.php` passes for cost-tracking scenario (3 tests total in suite, 23 assertions).
#### ⏭️ Pending For Full BRD Completion
- ROI and revenue-linked reporting (beyond CPL) is pending.
- Scheduled exports and campaign budget alerts are pending.
#### 👤 User Stories
- As a marketing manager, I can see cost per lead for each campaign/source.
#### ✅ Acceptance Criteria
- Given campaign spend data, when leads are attributed, then cost per lead is calculated.
#### ⚙️ Backend Design
- Models: CampaignSpend
- Services: CostTrackingService
- DB Schema: campaign_spends (campaign_id, amount, period)
#### 🎨 UI/UX
- Cost-per-lead dashboard, reports
#### 🔗 Dependencies
- Attribution, Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Spend entry, cost calculation

---

### Feature: Marketing Automation Engine (MA-001–MA-010)
#### 📌 Description
Visual workflow builder for multi-step automation: triggers, actions, A/B testing, drip, re-engagement, reporting.
#### ✅ Implemented In Current Build
- MA-001 initial foundation slice implemented:
	- Added core automation schema migrations: `automation_workflows`, `workflow_steps`, `workflow_instances`, `workflow_action_executions`.
	- Added CRM models with UUID + soft deletes + InstitutionScope for multi-tenant isolation.
	- Added workflow enums (`WorkflowStatus`, `WorkflowNodeType`) and typed workflow DTO for builder payload normalization.
	- Added repository + service layer for workflow CRUD and ordered step persistence.
	- Added web controller + API controller + API resource + validation requests for workflow management.
	- Added authenticated CRM routes under `/crm/marketing/automation-workflows` and integration API routes under `/api/v1/crm/automation/workflows`.
	- Added CRM sidebar navigation entry and initial builder/list Blade views with step add/remove/reorder and `steps_json` payload handling.
	- Added API feature tests for create/list/update/delete, institution isolation, and `steps_json` parsing.
- MA-002 trigger engine initial slice implemented:
	- Added `AutomationTriggerService` to evaluate event-based, date/time-based, and inactivity timeout triggers.
	- Added queued trigger jobs (`EvaluateAutomationTriggerJob`, `EvaluateTimedAutomationTriggersJob`, `EvaluateInactivityAutomationTriggersJob`) on `crm-automation` queue.
	- Added trigger listeners for `lead_created`, `form_submitted`, `status_changed`, `lead_score_changed`, `email_opened`, and `link_clicked` trigger types.
	- Added email webhook event dispatch for open/click events (`EmailOpenedEvent`, `EmailLinkClickedEvent`) to feed trigger evaluation.
	- Added scheduler wiring for timed/inactivity trigger scans in `routes/console.php`.
	- Added Horizon supervisor configuration for `crm-automation` queue.
	- Added MA-002 feature tests for trigger evaluation across lead_created, status_changed, date_time_based, and inactivity_timeout flows.
- MA-002 completed for current sprint scope:
	- Trigger type validation and builder options now cover all BRD MA-002 trigger families: lead created, form submitted, email opened, link clicked, lead score changed, status changed, date/time based, inactivity timeout.
	- Added trigger tests for `lead_score_changed` and `link_clicked` paths.
- MA-003 initial action runtime slice implemented:
	- Added `AutomationActionService` with action strategy handling for: send_email, send_sms, send_whatsapp, assign_counsellor, update_lead_field, enrol_in_workflow, webhook_call.
	- Added graceful pending stubs for `add_tag` and `create_task` until those module entities are available.
	- Added queued MA-003 action executor job `ExecuteWorkflowActionsJob` on `crm-automation`.
	- Updated MA-002 trigger service to dispatch MA-003 action execution after workflow instance creation.
	- Added MA-003 feature tests for update-lead-field action, webhook action, and pending action behavior.
- MA-003 completed for current sprint scope:
	- Implemented remaining MA-003 action types end-to-end by adding CRM `Tag` + `Task` entities and storage (`crm_tags`, `lead_tag`, `crm_tasks`).
	- `add_tag` action now creates/links tenant-scoped tags to lead records.
	- `create_task` action now creates tenant-scoped CRM follow-up tasks and writes timeline activity entries.
	- Hardened action executor to catch per-step exceptions and persist failed execution records without crashing workflow processing.
	- Extended MA-003 test suite to cover `assign_counsellor`, `update_lead_field`, `add_tag`, `create_task`, `enrol_in_workflow`, and `webhook_call` action flows.
- MA-005 completed for current sprint scope (drip campaign scheduling):
	- Updated MA action runtime to execute one workflow action step at a time using `current_workflow_step_id` progression.
	- Added delay-aware scheduling so each step honors `delay_minutes` before execution.
	- Added workflow instance scheduling context keys (`next_action_step_id`, `next_action_due_at`) for deterministic queue resumes.
	- Updated `ExecuteWorkflowActionsJob` to self-reschedule immediate or delayed follow-up runs until the workflow instance reaches `completed` status.
	- Added MA-005 feature coverage to assert delayed drip progression and non-immediate execution of future steps.
- MA-006 completed for current sprint scope (nurture exit on progression):
	- Added `NurtureExitService` to auto-exit active nurture workflow instances when a lead reaches `contacted` or higher statuses.
	- Integrated MA-006 exit orchestration in `LeadStatusWorkflowService` status-transition handling.
	- Added execution guard in `ExecuteWorkflowActionsJob` to skip instances already moved to terminal states such as `exited`.
	- Added MA-006 feature tests for contacted-triggered nurture exit and non-exit on unchanged `new_enquiry` status.
- MA-004 completed for current sprint scope (A/B testing for automated email sequences):
	- Added MA-004 variant resolution in `AutomationActionService` for `send_email` actions with deterministic variant assignment.
	- Added support for split and weighted A/B strategies using lead-specific hashing for stable variant selection.
	- Added variant-level overrides for subject/content (`subject`, `custom_body_html`) and persisted selected variant metadata in workflow action execution results.
	- Updated `EmailService` to honour `custom_body_html` override for automated A/B content variants.
	- Added MA-004 feature coverage in automation action tests validating variant metadata capture and selected-subject delivery.
- MA-007 completed for current sprint scope (re-engagement for cold/inactive leads):
	- Added dedicated `re_engagement` trigger type support in workflow request validation and builder options.
	- Added cold-lead re-engagement listener (`EvaluateReEngagementOnLeadTemperatureChanged`) to dispatch automation evaluation on COLD transitions.
	- Extended inactivity scan logic to enrol leads into `re_engagement` workflows configured with inactivity reason.
	- Added trigger-config reason matching (`cold` / `inactive`) in automation trigger evaluation.
	- Added MA-007 feature tests covering both cold and inactive re-engagement enrollment paths.
- MA-008 completed for current sprint scope (programme-specific nurture journeys):
	- Added programme-level trigger matching in `AutomationTriggerService` using `trigger_config.programme_ids` and `trigger_config.programme_codes`.
	- Configured journey enrollment checks to match against lead programme interests (`lead_programme_interests` / `crm_programmes`) before creating workflow instances.
	- Added MA-008 trigger test coverage validating positive and negative matching for programme ID and code combinations.
- MA-009 completed for current sprint scope (event-based automation journeys):
	- Added `event_based` workflow trigger support in API request validation and workflow builder trigger options.
	- Added event trigger evaluation in `AutomationTriggerService` with support for `event_at`, `event_type`, `window_minutes`, and `reminder_offsets_days`.
	- Added scheduled job `EvaluateEventBasedAutomationTriggersJob` to run event journey evaluation every 15 minutes.
	- Added MA-009 feature tests for due event enrollment and same-day reminder deduplication.
- MA-010 completed for current sprint scope (automation performance reporting):
	- Added integration API endpoint `GET /api/v1/crm/automation/workflows-performance` for workflow performance reporting.
	- Added `AutomationPerformanceReportService` with workflow-level and summary KPIs: instances, completion rate, actions success/failure, action success rate.
	- Added `IndexAutomationPerformanceReportRequest` for validated filters (`days`, `workflow_uuid`).
	- Added MA-010 API feature test covering report generation and KPI payload assertions.
#### ⏭️ Pending For Full BRD Completion
- MA-001 to MA-010 are implemented for current sprint scope.
#### 👤 User Stories
- As a marketing manager, I can automate nurture journeys and campaigns.
#### ✅ Acceptance Criteria
- Given a workflow, when triggers fire, then actions are executed as per configuration.
#### ⚙️ Backend Design
- Controllers: AutomationController
- Services: AutomationService
- DTOs: CreateWorkflowDTO, UpdateWorkflowDTO
- Jobs: ExecuteAutomationJob, ABTestJob
- Events: WorkflowTriggeredEvent, ActionExecutedEvent
- DB Schema: automation_workflows, workflow_steps, workflow_triggers, workflow_actions
- Queues: crm-automation
#### 🎨 UI/UX
- automation-workflow.blade.php (drag-and-drop builder, stats)
#### 🔗 Dependencies
- Communication Engine (F), Lead foundation (A), Analytics (K)
#### 🔐 Security / DPDP
- Consent-aware, opt-out, audit logs, no PII in logs
#### 🧪 Test Cases
- Workflow creation, trigger/action execution, A/B test, DPDP compliance

---

## ✅ Implementation Truth Snapshot (April 2026)
- LC-005, LC-006, LC-013, LC-016, and LC-017 are complete for the current sprint slice.
- MA-001, MA-002, MA-003, MA-004, MA-005, MA-006, MA-007, MA-008, MA-009, and MA-010 are complete for the current sprint slice.

---
