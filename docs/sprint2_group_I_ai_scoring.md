# Group I — AI & Advanced Scoring

## 🎯 Objective
Deliver AI-powered lead scoring, predictive churn, custom qualification questionnaires, sentiment analysis, chatbot, forecasting, anomaly detection, and nurture journey builder, building on the Lead Scoring Engine (Sprint 1, Group D).

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| LQ-003 | AI-assisted lead scoring | Should Have | ⏳ |
| LQ-009 | Custom qualification questionnaires | Should Have | ⏳ |
| LQ-010 | Predictive churn flag | Should Have | ⏳ |
| AI-004 | Sentiment analysis on inbound | Should Have | ⏳ |
| AI-006 | Conversational AI chatbot | Should Have | ✅ |
| AI-008 | Predictive enrolment forecasting | Should Have | ✅ |
| AI-009 | Anomaly detection for drop-offs | Should Have | ✅ Completed (backend + API + web dashboard + scheduler + tests) |
| AI-010 | AI-powered nurture journey builder | Should Have | ✅ Completed (backend + API + web dashboard + scheduler + tests) |
| AI-011 | Human-in-the-loop AI suggestion governance | Must Have | ✅ Completed (decision logging + API/web actions + UI controls + tests) |
| AI-012 | AI usage audit and DPDP compliance logs | Must Have | ✅ Completed (event-driven usage logs + API/web dashboard + tests) |

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
