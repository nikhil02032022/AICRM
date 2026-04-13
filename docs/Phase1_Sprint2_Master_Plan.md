# A2A-CRM Phase 1 Sprint 2 Master Plan
**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Phase:** 1 — Sprint 2 (Should Have / Phase 2)  
**Last Updated:** April 2026

---

## Sprint Groups Overview

| Group | Theme | BRD Req IDs | Status | Sprint Doc |
|-------|-------|-------------|--------|------------|
| **H** | Marketing Automation & Attribution | LC-005, LC-006, LC-013, LC-016, LC-017, MA-001 to MA-010 | 🚧 LC-005 initial slice implemented | (inline) |
| **I** | AI & Advanced Scoring | LQ-003, LQ-009, LQ-010, AI-004, AI-006, AI-008, AI-009, AI-010, AI-011, AI-012 | ✅ LQ-003 + LQ-009 + LQ-010 + AI-004 + AI-005 + AI-006 + AI-008 + AI-009 + AI-010 + AI-011 + AI-012 completed | (inline) |
| **J** | Telecalling & Gamification | TC-001, TC-002, TC-005, EC-010, MB-004, MB-006, MB-007 | ⏳ In Progress | (inline) |
| **K** | Customisation & Advanced Analytics | EC-005, AR-018, AR-020, SA-007, SA-011 | ⏳ In Progress | (inline) |
| **L** | Integrations & Document Management | DM-006, DM-007, EI-008, EI-010, AG-006, AG-008 | ⏳ In Progress | (inline) |

---

## Group H — Marketing Automation & Attribution

**Theme:** Marketing Automation, Multi-touch Attribution, Kiosk/Chat Lead Capture  
**Target Completion:** May 2026

| Req ID | Feature | Files |
|--------|---------|-------|
| LC-005 | Landing page builder for lead capture | `LandingPage`, `LandingPageController`, `LandingPageService`, `LandingPageWebController`, `PublicLandingPageController`, `resources/views/crm/marketing/landing-pages/*`, `resources/views/public/landing-page/show.blade.php` |
| LC-006 | Live chat widget for lead capture | `ChatWidget`, `ChatWidgetController`, `ChatWidgetService`, `chat-widget.blade.php` |
| LC-013 | Walk-in enquiry kiosk interface | `KioskController`, `KioskService`, `kiosk.blade.php` |
| LC-016 | Multi-touch attribution model | `AttributionService`, `LeadAttribution`, migration, UI integration |
| LC-017 | Cost-per-lead tracking | `CampaignSpend`, `CostTrackingService`, migration, UI integration |
| MA-001 to MA-010 | Visual workflow builder, triggers, actions, A/B testing, drip, re-engagement, reporting | `AutomationWorkflow`, `AutomationController`, `AutomationService`, `automation-workflow.blade.php` |

**Tests:** 20+ planned  
**Security/DPDP:** Consent, opt-out, no PII in logs, DPDP-compliant automation

---

## Group I — AI & Advanced Scoring

**Theme:** AI scoring, predictive churn, custom questionnaires, NBA, chatbot, anomaly detection  
**Target Completion:** June 2026

| Req ID | Feature | Files |
|--------|---------|-------|
| LQ-003 | AI-assisted lead scoring | `AiLeadScoringService`, `LeadScoringJob`, `ai-lead-score.blade.php` |
| LQ-009 | Custom qualification questionnaires | `QualificationQuestionnaire`, `QuestionnaireController`, `questionnaire.blade.php` |
| LQ-010 | Predictive churn flag | `ChurnDetectionService`, `churn-flag.blade.php` |
| AI-004 | Sentiment analysis on inbound | `SentimentAnalysisService`, `sentiment-job`, UI integration |
| AI-006 | Conversational AI chatbot | `ChatbotService`, `GenerateChatbotReplyJob`, `ChatbotEscalationEvent`, `ChatWidgetController`, `ChatWidgetWebController`, `crm/marketing/chat-widget/index.blade.php` |
| AI-008 | Predictive enrolment forecasting | `ForecastingService`, `GenerateEnrolmentForecastJob`, `ForecastGeneratedEvent`, `EnrolmentForecast`, `crm/scoring/forecast-dashboard.blade.php` |
| AI-009 | Anomaly detection for drop-offs | `AnomalyDetectionService`, `anomaly-alerts.blade.php` |
| AI-010 | AI-powered nurture journey builder | `NbaJourneyService`, `nba-journey.blade.php` |
| AI-011 | Human-in-the-loop AI suggestion governance | `AiSuggestionDecisionService`, `AiSuggestionDecision`, `StoreAiSuggestionDecisionRequest`, lead/journey AI decision actions |
| AI-012 | AI usage logs for audit/DPDP compliance | `AiUsageLoggingService`, `AiUsageLog`, `RecordAiUsageLogFromEvent`, `ai-usage-logs.blade.php` |

**Tests:** 15+ planned  
**Security/DPDP:** All AI logs, suggestions only, no PII in logs

---

## Group J — Telecalling & Gamification

**Theme:** Power dialler, call scripts, gamification, mobile OCR, offline mode  
**Target Completion:** June 2026

| Req ID | Feature | Files |
|--------|---------|-------|
| TC-001 | Power/auto-dialler | `DiallerService`, `DiallerController`, `dialler.blade.php` |
| TC-002 | Call scripts with branching | `CallScript`, `CallScriptController`, `call-script.blade.php` |
| TC-005 | Supervisor call monitoring | `CallMonitorService`, `call-monitor.blade.php` |
| EC-010 | Counsellor performance gamification | `GamificationService`, `gamification-dashboard.blade.php` |
| MB-004 | Business card scanner (OCR) | `OcrService`, `ocr-upload.blade.php` |
| MB-006 | Mobile offline mode | Mobile app update, sync logic |
| MB-007 | Biometric authentication | Mobile app update, auth logic |

**Tests:** 12+ planned  
**Security/DPDP:** Call consent, no PII in logs, DPDP for call recordings

---

## Group K — Customisation & Advanced Analytics

**Theme:** Custom fields, custom reports, scheduled reports, workflow templates, system health  
**Target Completion:** July 2026

| Req ID | Feature | Files |
|--------|---------|-------|
| EC-005 | Custom fields per institution | `CustomField`, `CustomFieldController`, `custom-field.blade.php` |
| AR-018 | Custom report builder | `CustomReport`, `CustomReportController`, `custom-report.blade.php` |
| AR-020 | Scheduled report delivery | `ReportScheduler`, `report-scheduler.blade.php` |
| SA-007 | Workflow/automation template library | `WorkflowTemplate`, `workflow-template.blade.php` |
| SA-011 | System health monitoring dashboard | `SystemHealthService`, `system-health.blade.php` |

**Tests:** 10+ planned  
**Security/DPDP:** Field-level RBAC, audit logs, DPDP for exports

---

## Group L — Integrations & Document Management

**Theme:** DigiLocker/Aadhaar, ERP/LMS/Alumni integration, agent comms, document verification  
**Target Completion:** July 2026

| Req ID | Feature | Files |
|--------|---------|-------|
| DM-006 | DigiLocker integration | `DigiLockerService`, `digilocker.blade.php` |
| DM-007 | Aadhaar eKYC | `AadhaarService`, `aadhaar-ekyc.blade.php` |
| EI-008 | Alumni module bridge | `AlumniBridgeService`, `alumni-bridge.blade.php` |
| EI-010 | LMS enrolment trigger | `LmsEnrolmentService`, `lms-enrolment.blade.php` |
| AG-006 | Agent commission workflow | `AgentCommissionService`, `agent-commission.blade.php` |
| AG-008 | Agent bulk comms tools | `AgentCommsService`, `agent-comms.blade.php` |

**Tests:** 8+ planned  
**Security/DPDP:** All integrations DPDP-compliant, audit logs

---

## Phase 1 BRD Coverage Tracker — Sprint 2 (Should Have/Phase 2)

| Req ID | Priority | Group | Status |
|--------|----------|-------|--------|
| LC-005 | Should Have | H | 🚧 Initial implementation delivered |
| LC-006 | Should Have | H | ⏳ |
| LC-013 | Should Have | H | ⏳ |
| LC-016 | Should Have | H | ⏳ |
| LC-017 | Should Have | H | ⏳ |
| LQ-003 | Should Have | I | ⏳ |
| LQ-009 | Should Have | I | ⏳ |
| LQ-010 | Should Have | I | ⏳ |
| EC-010 | Should Have | J | ⏳ |
| EC-018 | Should Have | J | ⏳ |
| EC-019 | Should Have | J | ⏳ |
| AR-018 | Should Have | K | ⏳ |
| AR-020 | Should Have | K | ⏳ |
| SA-007 | Should Have | K | ⏳ |
| SA-011 | Should Have | K | ⏳ |
| DM-006 | Should Have | L | ⏳ |
| DM-007 | Should Have | L | ⏳ |
| EI-008 | Should Have | L | ⏳ |
| EI-010 | Should Have | L | ⏳ |
| AG-006 | Should Have | L | ⏳ |
| AG-008 | Should Have | L | ⏳ |
| MB-004 | Should Have | J | ⏳ |
| MB-006 | Should Have | J | ⏳ |
| MB-007 | Should Have | J | ⏳ |
| AI-004 | Should Have | I | ⏳ |
| AI-006 | Should Have | I | ✅ Completed |
| AI-008 | Should Have | I | ✅ Completed |
| AI-009 | Should Have | I | ✅ Completed |
| AI-010 | Should Have | I | ✅ Completed |
| AI-011 | Must Have | I | ✅ Completed |
| AI-012 | Must Have | I | ✅ Completed |

---

## Appendix

- All features must reference BRD Req IDs in code comments for traceability.
- DPDP Act 2023 compliance is mandatory for all new modules.
- See [BRD_A2A_Educational_CRM_v1.0_1.md](BRD_A2A_Educational_CRM_v1.0_1.md) for full requirement details.
- Update this file after each sprint review.
- LC-005 now has an initial implementation slice: backend CRUD, public rendering, CRM UI, route wiring, and baseline tests. Drag-and-drop composition and expanded analytics remain pending.
- Group I has completed AI-008 with enrolment_forecasts persistence, forecasting service + async monthly job, API/web forecast dashboard endpoints, scheduler trigger, and passing forecast API/job tests.
- Group I has completed AI-009 with anomaly_alerts persistence, rolling-window anomaly detection service + async daily job, API/web anomaly dashboard endpoints, scheduler trigger, and passing anomaly API/job tests.
- Group I has completed AI-010 with nba_journeys persistence, segment-wise journey suggestion service + async daily job, API/web nurture journey dashboard endpoints, scheduler trigger, and passing journey API/job tests.
- Group I has completed AI-011 with ai_suggestion_decisions persistence, explicit Accept/Edit/Dismiss service + API/web endpoints, and counsellor-facing suggestion controls across lead and journey AI surfaces with passing API tests.
- Group I has completed AI-012 with ai_usage_logs persistence, event-driven immutable AI usage logging across generation and human decision events, API/web audit log access, and passing logging API/listener tests.
