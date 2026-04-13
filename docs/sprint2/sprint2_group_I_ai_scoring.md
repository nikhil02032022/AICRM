# Group I — AI & Advanced Scoring

## 🎯 Objective
Deliver AI-powered lead scoring, predictive churn, custom qualification questionnaires, sentiment analysis, chatbot, forecasting, anomaly detection, and nurture journey builder, building on the Lead Scoring Engine (Sprint 1, Group D).

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| LQ-003 | AI-assisted lead scoring | Should Have | ✅ Completed (backend + API + web integration + tests) |
| LQ-009 | Custom qualification questionnaires | Should Have | ✅ Completed (backend + API + web management + tests) |
| LQ-010 | Predictive churn flag | Should Have | ✅ Completed (backend + API + web scoring integration + tests) |
| AI-002 | Next Best Action recommendation engine | Must Have | ✅ Completed (backend + API + web sidebar integration + tests) |
| AI-003 | AI-assisted communication drafting (email/WhatsApp) | Must Have | ✅ Completed (backend + API + web sidebar integration + tests) |
| AI-004 | Sentiment analysis on inbound | Should Have | ✅ Completed (backend + API + web scoring integration + tests) |
| AI-005 | Daily counsellor priority lead list | Must Have | ✅ Completed (backend + scheduler + API + web page + tests) |
| AI-006 | Conversational AI chatbot | Should Have | ✅ |
| AI-008 | Predictive enrolment forecasting | Should Have | ✅ |
| AI-009 | Anomaly detection for drop-offs | Should Have | ✅ Completed (backend + API + web dashboard + scheduler + tests) |
| AI-010 | AI-powered nurture journey builder | Should Have | ✅ Completed (backend + API + web dashboard + scheduler + tests) |
| AI-011 | Human-in-the-loop AI suggestion governance | Must Have | ✅ Completed (decision logging + API/web actions + lead/journey UI controls + tests) |
| AI-012 | AI usage audit and DPDP compliance logs | Must Have | ✅ Completed (event-driven usage logs + API/web audit dashboard + tests) |

## 🔎 Must-Have AI Traceability (BRD CRM-AI-001 to CRM-AI-003)
| BRD Req ID | BRD Requirement | Sprint Mapping | Status | Notes |
|-----------|------------------|----------------|--------|-------|
| AI-001 | AI-assisted lead scoring shall predict conversion probability | Mapped to `LQ-003` implementation in Group I | ✅ Covered via LQ-003 | Current implementation delivers auditable AI score snapshots and rationale. |
| AI-002 | Next Best Action (NBA) recommendation engine | Implemented under Group I AI layer | ✅ Completed | Rule-assisted engine provides auditable recommendation, reasoning, confidence, and channels. |
| AI-003 | AI-assisted communication drafting (email/WhatsApp) | Implemented under Group I AI layer with channel-first draft generation | ✅ Completed | Counsellors can generate/review latest AI drafts (email/WhatsApp); ready for deeper template/campaign integration. |

## 🧩 Features Breakdown

### Feature: AI-Assisted Lead Scoring (LQ-003)
#### 📌 Description
Augment rule-based scoring with AI/ML models using historical conversion data and behaviour patterns.
#### 👤 User Stories
- As a counsellor, I see AI-generated lead scores and explanations.
#### ✅ Acceptance Criteria
- Given a lead, when AI scoring is enabled, then the score and rationale are visible and auditable.
#### ⚙️ Backend Design
- Services: AiLeadScoringService
- Jobs: LeadScoringJob (queue: crm-ai)
- Events: LeadAiScoreCalculatedEvent
- DB Schema: ai_lead_scores (lead_uuid, score, explanation, model_version, calculated_at)
#### 🎨 UI/UX
- ai-lead-score.blade.php (score, rationale, override)
#### 🔗 Dependencies
- Lead Scoring Engine (D), Anthropic API integration
#### 🔐 Security / DPDP
- No PII in logs, audit trail, suggestions only
#### 🧪 Test Cases
- Score calculation, override, audit log

---

### Feature: Custom Qualification Questionnaires (LQ-009)
#### 📌 Description
Configurable BANT-style questionnaires for lead qualification, per institution.
#### 👤 User Stories
- As an admin, I can define qualification questions; as a counsellor, I can fill them for leads.
#### ✅ Acceptance Criteria
- Given a lead, when a questionnaire is filled, then responses are stored and visible.
#### ⚙️ Backend Design
- Models: QualificationQuestionnaire
- Controllers: QuestionnaireController
- DB Schema: qualification_questionnaires, questionnaire_responses
#### 🎨 UI/UX
- questionnaire.blade.php (form, admin config)
#### 🔗 Dependencies
- Lead foundation (A)
#### 🔐 Security / DPDP
- No PII in logs, audit log
#### 🧪 Test Cases
- Create, assign, fill, report

---

### Feature: Predictive Churn Flag (LQ-010)
#### 📌 Description
Flag leads at risk of dropping off using inactivity and engagement signals.
#### 👤 User Stories
- As a counsellor, I see churn risk flags and can act proactively.
#### ✅ Acceptance Criteria
- Given a lead, when churn risk is high, then a flag and recommended action are shown.
#### ⚙️ Backend Design
- Services: ChurnDetectionService
- Jobs: ChurnDetectionJob (queue: crm-ai)
- Events: LeadChurnFlaggedEvent
- DB Schema: churn_flags (lead_uuid, risk_level, flagged_at)
#### 🎨 UI/UX
- churn-flag.blade.php (flag, action)
#### 🔗 Dependencies
- Lead activity, Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs, audit log
#### 🧪 Test Cases
- Churn detection, flag display

---

### Feature: Sentiment Analysis on Inbound (AI-004)
#### 📌 Description
Analyze inbound emails/chats for sentiment, flagging negative/urgent leads.
#### 👤 User Stories
- As a counsellor, I am alerted to negative sentiment leads.
#### ✅ Acceptance Criteria
- Given inbound communication, when sentiment is negative, then a flag/alert is generated.
#### ⚙️ Backend Design
- Services: SentimentAnalysisService
- Jobs: SentimentAnalysisJob (queue: crm-ai)
- Events: SentimentFlaggedEvent
- DB Schema: sentiment_flags (lead_uuid, channel, sentiment, flagged_at)
#### 🎨 UI/UX
- Sentiment flag in inbox, lead timeline
#### 🔗 Dependencies
- Communication Engine (F)
#### 🔐 Security / DPDP
- No PII in logs, audit log
#### 🧪 Test Cases
- Sentiment detection, alert

---

### Feature: Conversational AI Chatbot (AI-006)
#### 📌 Description
AI-powered chatbot for WhatsApp/web chat, handles FAQs, appointment booking, brochure delivery, escalation.
#### 👤 User Stories
- As a prospective student, I interact with the chatbot for info and booking.
#### ✅ Acceptance Criteria
- Given a chat, when user asks a question, then the bot responds or escalates.
#### ⚙️ Backend Design
- Services: ChatbotService (AI) + existing ChatWidgetService orchestration
- Controllers: ChatWidgetController (API) + ChatWidgetWebController (web)
- Jobs: GenerateChatbotReplyJob (queue: ai)
- Events: ChatbotEscalationEvent
- DB Schema: existing chat_leads transcript ledger (LC-006 reuse; no duplicate chatbot session table)
#### 🎨 UI/UX
- crm/marketing/chat-widget/index.blade.php with Generate AI Reply action
#### 🔗 Dependencies
- Communication Engine (F), Anthropic API
#### 🔐 Security / DPDP
- Consent-aware, audit log
#### 🧪 Test Cases
- FAQ, booking, escalation

#### ✅ Implementation Notes
- Added async AI reply generation endpoint for API: `/api/v1/crm/chat-widget/leads/{chatLead}/ai-reply`.
- Added web action endpoint for CRM operators: `/crm/marketing/chat-widget/{chatLead}/ai-reply`.
- AI reply engine now classifies intents (fees, eligibility, brochure, booking, handoff) and updates transcript.
- Human escalation auto-sets `handoff_status=pending_agent` and dispatches `ChatbotEscalationEvent`.

---

### Feature: Predictive Enrolment Forecasting (AI-008)
#### 📌 Description
Forecast enrolments by programme/batch using AI models.
#### 👤 User Stories
- As an admin, I see projected enrolments and trends.
#### ✅ Acceptance Criteria
- Given current pipeline, when forecast is run, then projections are shown with confidence intervals.
#### ⚙️ Backend Design
- Services: ForecastingService
- Jobs: GenerateEnrolmentForecastJob (queue: ai)
- Events: ForecastGeneratedEvent
- DB Schema: enrolment_forecasts (crm_programme_id, admission_cycle, forecast_count, confidence_score, generated_for_month)
#### 🎨 UI/UX
- crm/scoring/forecast-dashboard.blade.php (chart + table + monthly generate trigger)
#### 🔗 Dependencies
- Application pipeline, Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Forecast accuracy, display

#### ✅ Implementation Notes
- Added API endpoints:
	- `GET /api/v1/crm/scoring/enrolment-forecasts?for_month=YYYY-MM`
	- `POST /api/v1/crm/scoring/enrolment-forecasts/generate`
- Added web routes:
	- `GET /crm/scoring/enrolment-forecasts`
	- `POST /crm/scoring/enrolment-forecasts/generate`
- Added monthly scheduler trigger in `routes/console.php`.

---

### Feature: Anomaly Detection for Drop-offs (AI-009)
#### 📌 Description
Detect unusual drops in enquiry/application volume, alert managers.
#### 👤 User Stories
- As a manager, I am alerted to anomalies in funnel metrics.
#### ✅ Acceptance Criteria
- Given a drop-off, when anomaly is detected, then alert is sent.
#### ⚙️ Backend Design
- Services: AnomalyDetectionService
- Jobs: RunAnomalyDetectionJob (queue: ai)
- Events: AnomalyDetectedEvent
- DB Schema: anomaly_alerts (metric_name, current_value, baseline_value, deviation_percent, severity, detected_at)
#### 🎨 UI/UX
- anomaly-alerts.blade.php (alerts, dashboard)
#### 🔗 Dependencies
- Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Anomaly detection, alert

#### ✅ Implementation Notes
- Added anomaly persistence and domain artifacts:
	- `anomaly_alerts` migration + `AnomalyAlert` model
	- `AnomalyAlertResource` for API output
	- `GenerateAnomalyDetectionRequest` for trigger payload validation
- Added anomaly detection execution flow:
	- `AnomalyDetectionService` rolling-window detection for lead/application drop-offs
	- `RunAnomalyDetectionJob` orchestration with per-institution execution and `AnomalyDetectedEvent` dispatch
- Added API endpoints:
	- `GET /api/v1/crm/scoring/anomaly-alerts?for_date=YYYY-MM-DD`
	- `POST /api/v1/crm/scoring/anomaly-alerts/detect`
- Added web routes/UI integration:
	- `GET /crm/scoring/anomaly-alerts`
	- `POST /crm/scoring/anomaly-alerts/detect`
	- Dashboard view `crm/scoring/anomaly-alerts.blade.php` + sidebar navigation entry under AI.
- Added scheduler trigger:
	- Daily anomaly run at `06:45` in `routes/console.php`.
- Verified with passing tests:
	- `AnomalyAlertApiTest`
	- `RunAnomalyDetectionJobTest`

---

### Feature: AI-powered Nurture Journey Builder (AI-010)
#### 📌 Description
AI suggests optimal nurture journeys (timing, channel, content) for segments.
#### 👤 User Stories
- As a marketing manager, I get AI recommendations for nurture workflows.
#### ✅ Acceptance Criteria
- Given a segment, when AI is run, then journey suggestions are generated.
#### ⚙️ Backend Design
- Services: NbaJourneyService
- Jobs: GenerateNbaJourneyJob (queue: ai)
- Events: NbaJourneySuggestedEvent
- DB Schema: nba_journeys (segment_key, segment_label, confidence_score, rationale, steps, generated_for_date, suggested_at)
#### 🎨 UI/UX
- nba-journey.blade.php (suggestions, apply to workflow)
#### 🔗 Dependencies
- Marketing Automation (H), Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs, audit log
#### 🧪 Test Cases
- Suggestion accuracy, workflow integration

#### ✅ Implementation Notes
- Added AI-010 persistence and event artifacts:
	- `nba_journeys` migration + `NbaJourney` model
	- `NbaJourneyResource` and `NbaJourneySuggestedEvent`
	- `GenerateNbaJourneyRequest` for trigger payload validation
- Added journey generation backend:
	- `NbaJourneyService` for segment-wise journey suggestions (`hot_leads`, `warm_leads`, `cold_or_inactive`, `application_started`)
	- `GenerateNbaJourneyJob` orchestration with per-institution generation and event dispatch
- Added API endpoints:
	- `GET /api/v1/crm/scoring/nba-journeys?for_date=YYYY-MM-DD&segment=...`
	- `POST /api/v1/crm/scoring/nba-journeys/generate`
- Added web routes/UI integration:
	- `GET /crm/scoring/nba-journeys`
	- `POST /crm/scoring/nba-journeys/generate`
	- Dashboard view `crm/scoring/nba-journey.blade.php` + sidebar navigation entry.
- Added scheduler trigger:
	- Daily journey suggestion run at `07:00` in `routes/console.php`.
- Verified with passing tests:
	- `NbaJourneyApiTest`
	- `GenerateNbaJourneyJobTest`

---

## Implementation Log

### 2026-04-13 — LQ-009 Initial Backend Slice
- Added schema foundations:
	- `qualification_questionnaires`
	- `questionnaire_responses`
- Added domain layer:
	- `QualificationQuestionnaire`, `QuestionnaireResponse`
	- `QuestionnaireStatus` enum
	- DTOs for create/update and response upsert
- Added service/repository layer:
	- `QuestionnaireService`
	- `QuestionnaireRepositoryInterface` + `EloquentQuestionnaireRepository`
- Added API layer:
	- `QuestionnaireController`
	- Store/update/upsert FormRequests
	- Questionnaire/response JsonResources
	- Routes under `/api/v1/crm/scoring/questionnaires`
- Added web placeholder:
	- `QuestionnaireWebController@index`
	- `resources/views/crm/ai/questionnaires/index.blade.php`
- Added provider + permissions:
	- `CrmAiServiceProvider`
	- `crm.questionnaires.manage`, `crm.questionnaires.respond`
- Added tests:
	- `tests/Feature/CRM/Api/QuestionnaireApiTest.php` (3 passing tests)

### 2026-04-13 — LQ-003 Initial Backend Slice
- Added schema foundation:
	- `ai_lead_scores`
- Added domain + events:
	- `AiLeadScore`
	- `LeadAiScoreCalculatedEvent`
- Added AI scoring backend:
	- `AiLeadScoringService`
	- `RecalculateAiLeadScoreJob` (queue: `ai`)
	- Hooked into `RecalculateLeadScoreJob` for async AI augmentation
- Added model relation:
	- `Lead::aiLeadScores()`
- Added config readiness:
	- `services.anthropic` keys in `config/services.php` (model/api_key/timeout)
- Added tests:
	- `tests/Feature/CRM/Scoring/RecalculateAiLeadScoreJobTest.php`
	- Updated `tests/Feature/CRM/Scoring/RecalculateLeadScoreJobTest.php`

### 2026-04-13 — LQ-003 and LQ-009 Completion Pass
- Completed LQ-003 delivery:
	- Added AI score snapshot API endpoint and async trigger endpoint.
	- Added `AiLeadScoreResource` and API contract tests.
	- Integrated lead profile scoring tab with latest AI rationale and "Run AI Analysis" action.
	- Verified with passing tests:
		- `AiLeadScoringApiTest` (2 tests)
		- `RecalculateLeadScoreJobTest` (AI snapshot side effect)
		- `RecalculateAiLeadScoreJobTest`.
- Completed LQ-009 delivery:
	- Expanded questionnaire web flow to full CRUD (create/edit/archive) for admins.
	- Added counsellor response submission from lead scoring tab for active questionnaires.
	- Extended API coverage for update and archive operations.
	- Verified with passing tests:
		- `QuestionnaireApiTest` (5 tests).

### 2026-04-13 — LQ-010 Initial Backend Slice
- Added schema + domain foundations:
	- `churn_flags` table migration.
	- `ChurnFlag` model and `ChurnRiskLevel` enum.
	- `LeadChurnFlaggedEvent` for downstream workflow hooks.
- Added churn detection backend:
	- `ChurnDetectionService` with auditable indicator-based risk scoring.
	- `RecalculateLeadChurnRiskJob` on `ai` queue (unique per lead).
	- Auto-dispatch hook from `RecalculateAiLeadScoreJob` after AI score persistence.
- Added API surface:
	- `GET /api/v1/crm/leads/{lead}/churn-risk`
	- `POST /api/v1/crm/leads/{lead}/churn-risk/recalculate`
	- `ChurnFlagResource` response transformer.
- Added tests:
	- `ChurnFlagApiTest`
	- `RecalculateLeadChurnRiskJobTest`

### 2026-04-13 — LQ-010 Completion Pass
- Completed web integration for predictive churn:
	- Added lead scoring web endpoints for churn snapshot fetch and churn recalculation trigger.
	- Added churn risk panel in lead scoring tab with:
		- risk badge + score,
		- detected indicators,
		- recommended next actions,
		- one-click "Run Churn Analysis" action.
	- Loaded latest churn snapshot in lead show controller for counsellor decisioning context.
- Verified with passing tests:
	- `ChurnFlagApiTest`
	- `RecalculateLeadChurnRiskJobTest`
	- `AiLeadScoringApiTest`
	- `RecalculateAiLeadScoreJobTest`

### 2026-04-13 — AI-002 Completion Pass (Next Best Action)
- Added schema + domain foundations:
	- `lead_nba_recommendations` table migration.
	- `LeadNbaRecommendation` model.
	- `LeadNbaRecommendedEvent` for downstream orchestration hooks.
- Added NBA recommendation backend:
	- `NbaRecommendationService` (rule-assisted decisioning with reasoning + confidence + channels).
	- `RecalculateLeadNbaJob` on `ai` queue.
	- Chained dispatch from churn recalculation flow for refreshed recommendation generation.
- Added API/web surface:
	- `GET /api/v1/crm/leads/{lead}/next-best-action`
	- `POST /api/v1/crm/leads/{lead}/next-best-action/recalculate`
	- Web equivalents under `/crm/leads/{lead}/next-best-action...`.
	- `LeadNbaRecommendationResource` transformer.
- Added lead UI integration:
	- Replaced AI-002 placeholder with live recommendation card in lead sidebar.
	- Added one-click refresh action for counsellors.
- Added tests:
	- `LeadNbaApiTest`
	- `RecalculateLeadNbaJobTest`

### 2026-04-13 — AI-003 Completion Pass (AI Communication Drafting)
- Added schema + domain foundations:
	- `ai_message_drafts` table migration.
	- `AiMessageDraft` model.
	- `LeadAiMessageDraftedEvent` event.
- Added AI drafting backend:
	- `AiCommunicationDraftService` for channel-specific draft generation using lead score/churn/NBA context.
	- `GenerateLeadAiMessageDraftJob` on `ai` queue (unique by lead + channel).
- Added API/web surface:
	- `GET /api/v1/crm/leads/{lead}/ai-drafts?channel=email|whatsapp`
	- `POST /api/v1/crm/leads/{lead}/ai-drafts/generate`
	- Web equivalents under `/crm/leads/{lead}/ai-drafts...`.
	- `AiMessageDraftResource` transformer.
- Added lead UI integration:
	- Added AI Communication Draft card in lead sidebar.
	- Added channel selector and one-click "Generate Draft" action for counsellors.
	- Added latest draft preview (subject, text, generated timestamp).
- Added tests:
	- `AiMessageDraftApiTest`
	- `GenerateLeadAiMessageDraftJobTest`

### 2026-04-13 — AI-004 Completion Pass (Inbound Sentiment Analysis)
- Added schema + domain foundations:
	- `sentiment_flags` table migration.
	- `SentimentFlag` model and `SentimentLabel` enum.
	- `LeadSentimentFlaggedEvent` event.
- Added sentiment backend:
	- `SentimentAnalysisService` (inbound message heuristic sentiment + urgency classification).
	- `RecalculateLeadSentimentJob` on `ai` queue.
- Added API/web surface:
	- `GET /api/v1/crm/leads/{lead}/sentiment`
	- `POST /api/v1/crm/leads/{lead}/sentiment/recalculate`
	- Web equivalents under `/crm/leads/{lead}/sentiment...`.
	- `SentimentFlagResource` transformer.
- Added lead UI integration:
	- Added Inbound Sentiment Signal card in lead scoring tab.
	- Displays sentiment class, urgency badge, rationale, source excerpt, and timestamp.
	- Added one-click "Run Sentiment Scan" action.

### 2026-04-13 — AI-009 Completion Pass (Anomaly Detection)
- Added anomaly persistence + transport layer:
	- `anomaly_alerts` table migration.
	- `AnomalyAlert` model + `AnomalyAlertResource`.
	- `GenerateAnomalyDetectionRequest` validation contract.
- Added detection backend:
	- `AnomalyDetectionService` (rolling baseline deviation detection for lead and application submitted volumes).
	- `RunAnomalyDetectionJob` on `ai` queue with per-institution execution and `AnomalyDetectedEvent` dispatch.
- Added API/web integration:
	- API: `GET /api/v1/crm/scoring/anomaly-alerts`, `POST /api/v1/crm/scoring/anomaly-alerts/detect`.
	- Web: `GET/POST /crm/scoring/anomaly-alerts` with anomaly dashboard + trigger action.
	- Added AI sidebar navigation link for anomaly dashboard access.
- Added operations scheduling:
	- Daily scheduled anomaly detection at `06:45` in `routes/console.php`.
- Verified with passing tests:
	- `AnomalyAlertApiTest`.
	- `RunAnomalyDetectionJobTest`.

### 2026-04-13 — AI-010 Completion Pass (Nurture Journey Builder)
- Added AI-010 persistence + transport layer:
	- `nba_journeys` table migration.
	- `NbaJourney` model + `NbaJourneyResource`.
	- `GenerateNbaJourneyRequest` validation contract.
- Added journey suggestion backend:
	- `NbaJourneyService` for segment-wise cadence suggestions (channel + day offset + action).
	- `GenerateNbaJourneyJob` on `ai` queue with per-institution generation and `NbaJourneySuggestedEvent` dispatch.
- Added API/web integration:
	- API: `GET /api/v1/crm/scoring/nba-journeys`, `POST /api/v1/crm/scoring/nba-journeys/generate`.
	- Web: `GET/POST /crm/scoring/nba-journeys` with filter + trigger UI.
	- Added AI sidebar navigation link for Nurture Journeys.
- Added operations scheduling:
	- Daily scheduled journey generation at `07:00` in `routes/console.php`.
- Verified with passing tests:
	- `NbaJourneyApiTest`.
	- `GenerateNbaJourneyJobTest`.

### 2026-04-13 — AI-011 Completion Pass (Human Final Decision Governance)
- Added AI-011 persistence + transport layer:
	- `ai_suggestion_decisions` table migration.
	- `AiSuggestionDecision` model + `AiSuggestionDecisionResource`.
	- `StoreAiSuggestionDecisionRequest` validation contract.
- Added AI suggestion governance backend:
	- `AiSuggestionDecisionService` for explicit Accept/Edit/Dismiss capture.
	- `AiSuggestionDecisionRecordedEvent` dispatch for downstream audit/analytics hooks.
- Added API/web integration:
	- API: `POST /api/v1/crm/scoring/ai-suggestions/decision`.
	- Web: `POST /crm/scoring/ai-suggestions/decision`.
	- Controller wiring in API and Web scoring controllers.
- Added counsellor-facing UI controls:
	- Lead sidebar NBA card now includes suggestion-only notice with Accept/Dismiss actions.
	- Lead sidebar AI Draft card now includes suggestion-only notice with Accept/Dismiss/Edit actions.
	- Nurture Journey dashboard includes suggestion-only notice with Accept/Dismiss actions per journey.
- Verified with passing tests:
	- `AiSuggestionDecisionApiTest`.

### 2026-04-13 — AI-012 Completion Pass (AI Usage Logging)
- Added AI-012 persistence + transport layer:
	- `ai_usage_logs` table migration.
	- `AiUsageLog` model + `AiUsageLogResource`.
- Added centralized logging backend:
	- `AiUsageLoggingService` for immutable AI usage log writes.
	- `RecordAiUsageLogFromEvent` listener for AI generation/decision events.
	- Event registration in `AppServiceProvider` for AI score, NBA, draft, sentiment, anomaly, forecast, journey, and human decision events.
- Added API/web integration:
	- API: `GET /api/v1/crm/scoring/ai-usage-logs`.
	- Web: `GET /crm/scoring/ai-usage-logs` with filterable audit dashboard.
	- Added sidebar navigation entry: AI Usage Logs.
- Verified with passing tests:
	- `AiUsageLogApiTest`.
	- `RecordAiUsageLogFromEventTest`.
- Added tests:
	- `SentimentFlagApiTest`
	- `RecalculateLeadSentimentJobTest`

### 2026-04-13 — AI-005 Completion Pass (Daily Priority Lead List)
- Added schema + domain foundations:
	- `counsellor_priority_leads` table migration.
	- `CounsellorPriorityLead` model.
- Added AI prioritisation backend:
	- `PriorityLeadListService` for daily ranking using score, inactivity, conversion probability.
	- `GenerateDailyPriorityLeadListJob` (institution-level trigger + all-institutions scheduled mode).
	- Daily scheduler entry added in `routes/console.php`.
- Added API/web surface:
	- `GET /api/v1/crm/scoring/priority-leads`
	- `POST /api/v1/crm/scoring/priority-leads/generate`
	- Web routes under `/crm/scoring/priority-leads...`.
	- `CounsellorPriorityLeadResource` transformer.
- Added web UI integration:
	- New counsellor page `crm/scoring/priority-leads.blade.php` with date selector + ranked list table.
	- Sidebar navigation entry under AI & Qualification.
- Added tests:
	- `PriorityLeadListApiTest`
	- `GenerateDailyPriorityLeadListJobTest`

### 2026-04-13 — AI-008 Completion Pass (Predictive Enrolment Forecasting)
- Added schema + domain foundations:
	- `enrolment_forecasts` table migration.
	- `EnrolmentForecast` model.
	- `ForecastGeneratedEvent` event.
- Added forecasting backend:
	- `ForecastingService` for programme-wise forecast, confidence score, and input-signal persistence.
	- `GenerateEnrolmentForecastJob` on `ai` queue.
	- Monthly scheduler entry added in `routes/console.php`.
- Added API/web surface:
	- `GET /api/v1/crm/scoring/enrolment-forecasts`
	- `POST /api/v1/crm/scoring/enrolment-forecasts/generate`
	- Web routes under `/crm/scoring/enrolment-forecasts...`.
	- `EnrolmentForecastResource` transformer.
- Added web UI integration:
	- New admin dashboard `crm/scoring/forecast-dashboard.blade.php` with monthly trigger, KPI cards, charts, and detailed table.
	- Sidebar navigation entry under AI & Qualification.
- Added tests:
	- `EnrolmentForecastApiTest`
	- `GenerateEnrolmentForecastJobTest`
