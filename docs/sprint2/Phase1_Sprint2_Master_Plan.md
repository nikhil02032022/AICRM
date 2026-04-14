# A2A-CRM Phase 1 Sprint 2 Master Plan
**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Phase:** 1 — Sprint 2 (Should Have / Phase 2)  
**Last Updated:** April 2026

---

## Sprint Groups Overview

| Group | Theme | BRD Req IDs | Status | Sprint Doc |
|-------|-------|-------------|--------|------------|
| **H** | Marketing Automation & Attribution | LC-005, LC-006, LC-013, LC-016, LC-017, MA-001 to MA-010 | 🚧 LC-005/006/013/016/017 slice implemented | (inline) |
| **I** | AI & Advanced Scoring | LQ-003, LQ-009, LQ-010, AI-004, AI-006, AI-008, AI-009, AI-010, AI-011, AI-012 | ✅ LQ-003 + LQ-009 + LQ-010 + AI-004 + AI-005 + AI-006 + AI-008 + AI-009 + AI-010 + AI-011 + AI-012 completed | (inline) |
| **J** | Telecalling & Gamification | TC-001, TC-002, TC-003, TC-004, TC-005, TC-006, EC-010, MB-004, MB-006, MB-007 | 🚧 TC-001 to TC-006 core telecalling items completed; remaining items in progress | (inline) |
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
| LQ-003 | AI-assisted lead scoring | `AiLeadScoringService`, `RecalculateAiLeadScoreJob`, `AiLeadScore`, `LeadAiScoreCalculatedEvent`, `ai-lead-score.blade.php` |
| LQ-009 | Custom qualification questionnaires | `QualificationQuestionnaire`, `QuestionnaireResponse`, `QuestionnaireController`, `QuestionnaireService`, `QuestionnaireRepositoryInterface`, `questionnaire-api-tests` |
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
| TC-001 | Power/auto-dialler | `DiallerSession`, `DiallerLog`, `DiallerService`, `DiallerJob`, `DiallerWebController`, `DiallerController`, `dialler.blade.php` |
| TC-002 | Call scripts with branching | `CallScript`, `CallScriptStep`, `CallScriptService`, `CallScriptController`, `CallScriptWebController`, `call-script.blade.php` |
| TC-003 | Configurable call dispositions | `CallDispositionConfig`, `CallDispositionService`, `CallDispositionWebController`, `CallDispositionController`, `dispositions.blade.php` |
| TC-004 | Post-call follow-up scheduling prompt | `CallLogWebController`, `CallDispositionService`, `SessionWebController`, `crm/sessions/create.blade.php` |
| TC-005 | Supervisor call monitoring | `CallMonitorLog`, `CallMonitorService`, `CallMonitorWebController`, `CallMonitorController`, `call-monitor.blade.php` |
| TC-006 | Calling campaign management | `TelecallingCampaign`, `TelecallingCampaignService`, `TelecallingCampaignWebController`, `TelecallingCampaignController`, `campaigns.blade.php` |
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
**Status: ✅ COMPLETED**

| Req ID | Feature | Files |
|--------|---------|-------|
| DM-006 | DigiLocker integration | `DigiLockerService`, `digilocker.blade.php` |
| DM-007 | Aadhaar eKYC | `AadhaarService`, `aadhaar-ekyc.blade.php` |
| EI-008 | Alumni module bridge | `AlumniBridgeService`, `alumni-bridge.blade.php` |
| EI-010 | LMS enrolment trigger | `LmsEnrolmentService`, `lms-enrolment.blade.php` |
| AG-006 | Agent commission workflow | `AgentCommissionService`, `commission.blade.php` |
| AG-008 | Agent bulk comms tools | `AgentCommsService`, `comms.blade.php` |

**Tests:** `DigiLockerTest`, `AadhaarEkycTest`, `AlumniBridgeTest`, `LmsEnrolmentTest`, `AgentCommissionTest`, `AgentCommsTest` — 24 tests  
**Security/DPDP:** Aadhaar numbers never stored; opt-out respected; consent_record_id required for DigiLocker

---

## Phase 1 BRD Coverage Tracker — Sprint 2 (Should Have/Phase 2)

| Req ID | Priority | Group | Status |
|--------|----------|-------|--------|
| LC-005 | Should Have | H | 🚧 Current slice implemented |
| LC-006 | Should Have | H | 🚧 Current slice implemented |
| LC-013 | Should Have | H | 🚧 Current slice implemented |
| LC-016 | Should Have | H | 🚧 Current slice implemented |
| LC-017 | Should Have | H | 🚧 Current slice implemented |
| LQ-003 | Should Have | I | ✅ Completed |
| LQ-009 | Should Have | I | ✅ Completed |
| LQ-010 | Should Have | I | ✅ Completed |
| EC-010 | Should Have | J | ⏳ |
| EC-018 | Should Have | J | ⏳ |
| EC-019 | Should Have | J | ⏳ |
| AR-018 | Should Have | K | ⏳ |
| AR-020 | Should Have | K | ⏳ |
| SA-007 | Should Have | K | ⏳ |
| SA-011 | Should Have | K | ⏳ |
| DM-006 | Should Have | L | ✅ Completed |
| DM-007 | Should Have | L | ✅ Completed |
| EI-008 | Should Have | L | ✅ Completed |
| EI-010 | Should Have | L | ✅ Completed |
| AG-006 | Should Have | L | ✅ Completed |
| AG-008 | Should Have | L | ✅ Completed |
| MB-004 | Should Have | J | ⏳ |
| MB-006 | Should Have | J | ⏳ |
| MB-007 | Should Have | J | ⏳ |
| TC-001 | Should Have | J | ✅ Completed |
| TC-002 | Should Have | J | ✅ Completed |
| TC-003 | Must Have | J | ✅ Completed |
| TC-004 | Must Have | J | ✅ Completed |
| TC-005 | Should Have | J | ✅ Completed |
| TC-006 | Must Have | J | ✅ Completed |
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
- Group H now has a delivered current slice for LC-005, LC-006, LC-013, LC-016, and LC-017 including backend services, migrations, web/API routes, and baseline tests. MA-001 to MA-010 remain pending.
- Group I has completed LQ-009 with migrations, models, repository/service, API routes/controllers/resources, web CRUD/response capture, provider bindings, and passing API tests.
- Group I has completed LQ-003 with ai_lead_scores persistence, async AI scoring job flow, scoring-job integration hook, API fetch/trigger endpoints, lead scoring tab integration, and passing API/scoring tests.
- Group I has completed LQ-010 with churn_flags persistence, churn detection service + async job, API fetch/trigger endpoints, lead scoring tab churn panel + recommendations, and passing churn API/job tests.
- BRD traceability note: AI-001 is covered via Group I LQ-003 implementation; AI-002 (Next Best Action) and AI-003 (AI-assisted communication drafting) are implemented in Group I.
- Group I has completed AI-004 with sentiment_flags persistence, inbound sentiment analysis service + async job, API/web fetch/trigger endpoints, lead scoring sentiment panel, and passing sentiment API/job tests.
- Group I has completed AI-005 with counsellor_priority_leads daily snapshots, score/inactivity/probability ranking service, scheduled daily generation, API/web priority list access, and passing priority API/job tests.
- Group I has completed AI-006 with conversational chatbot reply generation, async AI queue job, escalation event dispatch, API/web trigger routes, chat queue UI action, and passing chatbot API/job tests.
- Group I has completed AI-008 with enrolment_forecasts persistence, forecasting service + async monthly job, API/web forecast dashboard endpoints, scheduler trigger, and passing forecast API/job tests.
- Group I has completed AI-009 with anomaly_alerts persistence, rolling-window anomaly detection service + async daily job, API/web anomaly dashboard endpoints, scheduler trigger, and passing anomaly API/job tests.
- Group I has completed AI-010 with nba_journeys persistence, segment-wise journey suggestion service + async daily job, API/web nurture journey dashboard endpoints, scheduler trigger, and passing journey API/job tests.
- Group I has completed AI-011 with ai_suggestion_decisions persistence, explicit Accept/Edit/Dismiss service + API/web endpoints, and counsellor-facing suggestion controls across lead and journey AI surfaces with passing API tests.
- Group I has completed AI-012 with ai_usage_logs persistence, event-driven immutable AI usage logging across generation and human decision events, API/web audit log access, and passing logging API/listener tests.
- Group J has completed TC-001 end-to-end with dialler_sessions/dialler_logs persistence, queued DiallerJob flow on `crm-telecalling`, consent/DNC-safe lead filtering, event-driven queue progression from call completion, web/API dialler session controls, and passing feature/API tests.
- Group J has completed TC-002 end-to-end with call_scripts/call_script_steps persistence, repository-service branching engine, web/API CRUD + resolve endpoints, call script runner UI, and passing feature/API tests.
- Group J has completed TC-003 end-to-end with institution-scoped call_disposition_configs, web/API disposition management, and call log validation against active configured outcomes.
- Group J has completed TC-004 end-to-end with disposition-driven follow-up prompt routing and a working counselling session scheduling form for immediate post-call booking.
- Group J has completed TC-005 end-to-end with call_monitor_logs persistence, consent-aware monitor session controls (listen/whisper/barge-in), web/API monitor dashboards, and passing feature/API tests.
- Group J has completed TC-006 end-to-end with telecalling_campaigns + assignment tables, web/API campaign management endpoints, dedicated web edit screen, dialler session campaign linkage, time-window/tenant-safe assignment validation, campaign progress tracking, and passing web/API tests.
- Group L has completed DM-006 with digilocker_documents persistence, DigiLockerService (initiateRequest → VerifyDigiLockerDocumentJob on crm-integrations, markVerified, markFailed), DigiLockerVerifiedEvent, web/API controllers, DigiLocker Blade view with initiate modal, DPDP-safe consent_record_id requirement, InstitutionScope tenant isolation, and passing service/isolation tests.
- Group L has completed DM-007 with aadhaar_ekyc_logs persistence (Aadhaar number NEVER stored — UIDAI/DPDP compliance), AadhaarService (initiate → ProcessAadhaarKycJob, verifyOtp, markFailed), AadhaarKycCompletedEvent, web/API controllers, Aadhaar eKYC Blade view with OTP verify modal, and passing DPDP + service tests.
- Group L has completed EI-008 with alumni_bridge_logs persistence, AlumniBridgeService (trigger → TriggerAlumniBridgeJob + AlumniBridgeTriggeredEvent, markSuccess with erp_alumni_id, incrementReferrals), web/API controllers, Alumni Bridge Blade view, and passing service/event tests.
- Group L has completed EI-010 with lms_enrolment_logs persistence (camplus/moodle providers), LmsEnrolmentService (trigger → TriggerLmsEnrolmentJob, markEnrolled with lms_user_id, incrementAttempts, markFailed), web/API controllers, LMS Enrolment Blade view with provider filter, and passing service tests.
- Group L has completed AG-006 with agent_commissions persistence (fixed/percentage types, approval workflow), AgentCommissionService (create → ProcessAgentCommissionJob, approve → AgentCommissionApprovedEvent, reject, markPaid with payout_reference), web/API controllers, Commission Blade view with approve/reject/pay action buttons, and passing full workflow tests.
- Group L has completed AG-008 with agent_comms_logs persistence, AgentCommsService (send → SendAgentBulkCommsJob, recordDelivery → AgentBulkCommsSentEvent), DPDP opt_out_respected flag always true, web/API controllers, Comms Blade view with channel/message compose modal, InstitutionScope isolation, and passing DPDP + scope tests.
- Group L: CrmIntegrationServiceProvider registered in bootstrap/providers.php; all 6 repository interface bindings (DigiLocker, Aadhaar, AlumniBridge, LmsEnrolment, AgentCommission, AgentComms) wired.
