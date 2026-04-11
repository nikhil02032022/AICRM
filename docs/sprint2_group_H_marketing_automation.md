# Group H — Marketing Automation & Attribution

## 🎯 Objective
Deliver advanced marketing automation, multi-touch attribution, kiosk/chat lead capture, and cost-per-lead tracking for A2A-CRM, building on the Communication Engine (Sprint 1, Group F) and Lead Capture foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| LC-005 | Landing page builder for lead capture | Should Have | ✅ Implemented (current slice) |
| LC-006 | Live chat widget for lead capture | Should Have | ✅ Implemented (current slice) |
| LC-013 | Walk-in enquiry kiosk interface | Should Have | ✅ Implemented (current slice) |
| LC-016 | Multi-touch attribution model | Should Have | ✅ Implemented (current slice) |
| LC-017 | Cost-per-lead tracking | Should Have | ✅ Implemented (current slice) |
| MA-001–MA-010 | Visual workflow builder, triggers, actions, A/B testing, drip, re-engagement, reporting | Must/Should Have | ❌ Not Implemented |

## 📊 Current Build Summary
- Fully implemented: LC-005, LC-006, LC-013, LC-016, LC-017 (current slice)
- Not implemented: MA-001 to MA-010

## 🧩 Features Breakdown

### Feature: Landing Page Builder (LC-005)
#### 📌 Description
Institutions can create custom landing pages for campaigns, mapped to lead capture and attribution.
#### 🛠️ Implementation Log
- Added `landing_pages` migration and `LandingPage` model with UUIDs, soft deletes, tenant scoping, publish state, SEO, content blocks, and attribution parameters.
- Added repository, DTO, service, event, policy, API resource, API controller, CRM web controller, and public controller for published landing page rendering.
- Wired authenticated CRM routes under `/crm/marketing/landing-pages`, integration API routes under `/api/v1/crm/landing-pages`, and public published-page routes under `/lp/{slug}`.
- Added CRM Blade management screens plus a public-facing landing page template that embeds the existing CRM web form flow instead of duplicating lead-capture logic.
- Redesigned the public landing page UI with improved responsive hierarchy, stronger CTA emphasis, bento-style value sections, upgraded typography, and accessibility-focused touch targets while preserving CRM form embed and attribution behavior.
- Rebuilt the public landing page again using UI/UX Pro skill guidance with a cleaner conversion-first layout, stricter Tailwind-only visual tokens, improved readability hierarchy, and responsive interaction polish for mobile and desktop.
- Refined public-page copy to remove technical jargon (status, form wiring, funnel/capture wording) and keep all visitor-facing text plain-language and student-friendly.
- Updated landing-page form embed container to auto-resize based on loaded form content height (instead of fixed iframe height) for better fit across short and long form schemas.
- Added initial API and public tests covering create/update/delete, institution isolation, published visibility, and draft 404 behaviour.
#### ✅ Delivered Scope In This Slice
- Campaign landing page CRUD for CRM staff
- Public published landing page rendering
- Reuse of existing DPDP-compliant web form embed flow for lead capture
- Attribution parameter propagation to the embedded form URL
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
- Drag-and-drop landing page builder interactions
- Template library and richer section/block composition
- Deeper campaign analytics and attribution reporting integration
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

---

### Feature: Live Chat Widget (LC-006)
#### 📌 Description
Embeddable chat widget for websites, auto-creates CRM leads from chat sessions.
#### 🛠️ Implementation Log
- Added `chat_leads` migration and `ChatLead` model with UUIDs, soft deletes, tenant scoping, transcript storage, attribution parameters, and DPDP consent fields.
- Added `CreateChatLeadDTO`, marketing repository + service layer, `ChatLeadCreatedEvent`, and `ProcessChatLeadJob` to process captured sessions asynchronously.
- Implemented public widget endpoints: `GET /chat/widget/{institution:uuid}` and `POST /chat/widget/{institution:uuid}/submit` to capture transcript + consent and create CRM leads.
- Implemented CRM web management screen under `/crm/marketing/chat-widget` with embed snippet, preview link, and captured chat lead listing.
- Implemented integration API endpoints under `/api/v1/crm/chat-widget/leads` for authenticated external ingestion and retrieval.
#### ✅ Delivered Scope In This Slice
- Embeddable live chat widget UI for institution websites (iframe based).
- Chat transcript + UTM capture with DPDP consent enforcement.
- Automatic CRM lead creation from chat submissions (`source=live_chat`).
- Session tracking table for chat enquiries with lead linkage and async processing marker.
#### 🔎 Code Evidence
- `app/Models/CRM/ChatLead.php`
- `app/Services/CRM/Marketing/ChatWidgetService.php`
- `app/Http/Controllers/Public/PublicChatWidgetController.php`
- `app/Http/Controllers/Web/CRM/ChatWidgetWebController.php`
- `app/Http/Controllers/Api/CRM/ChatWidgetController.php`
- `resources/views/public/chat-widget/show.blade.php`
- `resources/views/crm/marketing/chat-widget/index.blade.php`
- `database/migrations/2026_04_22_000001_create_chat_leads_table.php`
#### ⏭️ Pending For Full BRD Completion
- Real-time two-way agent chat (current slice captures transcript-driven enquiries, not live agent handoff).
- Chat routing/assignment workflow and unified inbox threading integration.
- Deeper analytics for chat response SLA, conversion stages, and campaign effectiveness.
#### 👤 User Stories
- As a prospective student, I can chat and become a lead.
#### ✅ Acceptance Criteria
- Given a chat session, when user submits contact, then a lead is created and attributed.
#### ⚙️ Backend Design
- Controllers: ChatWidgetController (Web, API)
- Services: ChatWidgetService
- DTOs: CreateChatLeadDTO
- Jobs: ProcessChatLeadJob
- Events: ChatLeadCreatedEvent
- DB Schema: chat_leads (uuid, institution_id, session, transcript, attribution, ...)
#### 🎨 UI/UX
- chat-widget.blade.php (embed, chat flow)
#### 🔗 Dependencies
- Communication Engine (F), Lead foundation (A)
#### 🔐 Security / DPDP
- Consent prompt, no PII in logs
#### 🧪 Test Cases
- Chat flow, lead creation, DPDP consent

---

### Feature: Walk-in Enquiry Kiosk (LC-013)
#### 📌 Description
Kiosk-friendly interface for walk-in lead capture at events/campus.
#### ✅ Implemented In Current Build
- Walk-in source support exists in lead domain enums/scoring.
#### ⏭️ Pending For Full BRD Completion
- No dedicated kiosk experience exists yet.
- No `KioskController`, `KioskService`, `CreateKioskLeadDTO`, `KioskLeadCreatedEvent`, kiosk routes, or `kiosk.blade.php` are implemented.
#### 👤 User Stories
- As a staff member, I can register walk-in enquiries quickly.
#### ✅ Acceptance Criteria
- Given a walk-in, when details are entered, then a lead is created with source=kiosk.
#### ⚙️ Backend Design
- Controllers: KioskController
- Services: KioskService
- DTOs: CreateKioskLeadDTO
- Jobs: N/A
- Events: KioskLeadCreatedEvent
- DB Schema: leads (source=kiosk)
#### 🎨 UI/UX
- kiosk.blade.php (touch-friendly form)
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
- None
#### ⏭️ Pending For Full BRD Completion
- Workflow engine is not yet implemented.
- No automation models, DTOs, jobs, events, DB tables, controllers, or workflow builder UI currently exist for MA-001 to MA-010.
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
- MA-001 to MA-010 remain pending implementation.

---
