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
| AI-006 | Conversational AI chatbot | Should Have | ⏳ |
| AI-008 | Predictive enrolment forecasting | Should Have | ⏳ |
| AI-009 | Anomaly detection for drop-offs | Should Have | ⏳ |
| AI-010 | AI-powered nurture journey builder | Should Have | ⏳ |

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
- Services: ChatbotService
- Controllers: ChatbotController (API)
- Jobs: ChatbotJob (queue: crm-ai)
- Events: ChatbotEscalationEvent
- DB Schema: chatbot_sessions, chatbot_messages
#### 🎨 UI/UX
- chatbot.blade.php (web), WhatsApp integration
#### 🔗 Dependencies
- Communication Engine (F), Anthropic API
#### 🔐 Security / DPDP
- Consent-aware, audit log
#### 🧪 Test Cases
- FAQ, booking, escalation

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
- Jobs: ForecastingJob (queue: crm-ai)
- Events: ForecastGeneratedEvent
- DB Schema: enrolment_forecasts (programme_id, batch_id, forecast, confidence, generated_at)
#### 🎨 UI/UX
- forecast-dashboard.blade.php (charts, trends)
#### 🔗 Dependencies
- Application pipeline, Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Forecast accuracy, display

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
- Jobs: AnomalyDetectionJob (queue: crm-ai)
- Events: AnomalyDetectedEvent
- DB Schema: anomaly_alerts (type, value, detected_at)
#### 🎨 UI/UX
- anomaly-alerts.blade.php (alerts, dashboard)
#### 🔗 Dependencies
- Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Anomaly detection, alert

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
- Jobs: NbaJourneyJob (queue: crm-ai)
- Events: NbaJourneySuggestedEvent
- DB Schema: nba_journeys (segment_id, suggestion, generated_at)
#### 🎨 UI/UX
- nba-journey.blade.php (suggestions, apply to workflow)
#### 🔗 Dependencies
- Marketing Automation (H), Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs, audit log
#### 🧪 Test Cases
- Suggestion accuracy, workflow integration

---
