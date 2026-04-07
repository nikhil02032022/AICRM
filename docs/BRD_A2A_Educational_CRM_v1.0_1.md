# Business Requirements Document
## A2A Educational CRM Module
### Admissions-2-Alumni (A2A) ERP — MEETCS Pvt. Ltd.

---

| **Document Attribute** | **Details** |
|---|---|
| Document Title | Business Requirements Document — A2A Educational CRM |
| Document ID | MEETCS-BRD-CRM-001 |
| Version | 1.0 |
| Status | Draft — For Review |
| Prepared By | Product Management, MEETCS Pvt. Ltd. |
| Date | April 2026 |
| Classification | Internal / Confidential |

---

## Document Control

| Version | Date | Author | Change Description |
|---|---|---|---|
| 0.1 | April 2026 | Product Team | Initial Draft |
| 1.0 | April 2026 | Product Team | Baseline BRD — Issued for Review |

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Business Context and Strategic Rationale](#2-business-context-and-strategic-rationale)
3. [Scope](#3-scope)
4. [Stakeholders](#4-stakeholders)
5. [Business Objectives and Success Metrics](#5-business-objectives-and-success-metrics)
6. [Competitive Landscape Analysis](#6-competitive-landscape-analysis)
7. [Assumptions, Constraints and Dependencies](#7-assumptions-constraints-and-dependencies)
8. [Functional Requirements](#8-functional-requirements)
   - 8.1 Lead Capture and Source Management
   - 8.2 Lead Qualification and Scoring
   - 8.3 Enquiry and Counselling Management
   - 8.4 Application and Admission Pipeline Management
   - 8.5 Multi-Channel Communication Engine
   - 8.6 Marketing Automation
   - 8.7 Fee, Scholarship and Payment Management
   - 8.8 Document Management
   - 8.9 Task, Activity and Follow-Up Management
   - 8.10 Telecalling and Call Centre Module
   - 8.11 Student Portal and Self-Service
   - 8.12 Agent and Channel Partner Management
   - 8.13 Alumni Lifecycle Bridge
   - 8.14 Analytics, Dashboards and Reporting
   - 8.15 AI and Agentic Intelligence Layer
   - 8.16 Mobile Application
   - 8.17 ERP Integration Layer (A2A Native)
   - 8.18 Third-Party Integrations
   - 8.19 System Administration and Configuration
9. [Non-Functional Requirements](#9-non-functional-requirements)
10. [Data Requirements](#10-data-requirements)
11. [Compliance and Regulatory Requirements](#11-compliance-and-regulatory-requirements)
12. [User Roles and Permissions](#12-user-roles-and-permissions)
13. [Feature Priority Matrix](#13-feature-priority-matrix)
14. [Glossary](#14-glossary)
15. [Appendices](#15-appendices)

---

## 1. Executive Summary

MEETCS Pvt. Ltd. intends to design, build and deploy a best-in-class **Educational CRM module** natively integrated within its **Admissions-2-Alumni (A2A) Educational ERP** platform. This module, referred to herein as **A2A-CRM**, will serve as the primary system of engagement for prospective student acquisition, enquiry management, admissions pipeline tracking, enrolment conversion, and lifecycle management right through to alumni engagement.

The A2A-CRM is designed to replace dependency on third-party CRM platforms such as Meritto, LeadSquared, ExtraAedge, Mastersoft CRM and Salesforce Education Cloud for institutions already on or evaluating the A2A ERP ecosystem. It leverages MEETCS's unique position as an integrated ERP provider to offer seamless, zero-friction data continuity across the entire student lifecycle — from first enquiry to graduation and beyond.

This document establishes the complete set of business requirements that will guide the design, development, testing and deployment of the A2A-CRM module.

---

## 2. Business Context and Strategic Rationale

### 2.1 Market Opportunity

The Indian higher education market comprises over 1,000 universities and 40,000+ colleges, with growing K-12 chains, EdTech platforms, coaching institutes, and vocational training providers all facing intensifying competition for student enrolments. Current CRM solutions in use by Indian educational institutions are either:

- Generic CRMs (Salesforce, Zoho, HubSpot) that require heavy customisation and lack native education workflows, or
- Point solutions (Meritto, ExtraAedge, LeadSquared) focused exclusively on admissions, with no continuity into the post-enrolment ERP world.

This creates a **structural integration gap**: institutions must manage multiple, disconnected systems for CRM, ERP, LMS, and HRMS, leading to data silos, duplicate records, manual reconciliation effort, and lost conversion opportunities.

### 2.2 MEETCS Strategic Position

MEETCS, through the A2A ERP platform, already manages the post-admission lifecycle: academics, examinations, student services, alumni, and HR. By extending A2A into the **pre-admission CRM space**, MEETCS can offer institutions a single, truly unified system spanning Enquiry → Admission → Student → Alumni — a capability no current competitor in the Indian market offers end-to-end.

### 2.3 Key Strategic Drivers

- Increase A2A ERP platform stickiness and reduce churn by eliminating reliance on third-party CRM tools
- Open a new revenue stream: A2A-CRM can be licensed standalone to institutions not yet on the full ERP
- Leverage the existing A2A data model (student master, programmes, fee structures, academic calendar) to eliminate duplicate configuration
- Embed AI and agentic intelligence from day one, building on MEETCS's existing investment in the Anthropic API-powered CRM engine within A2A
- Comply natively with India's **Digital Personal Data Protection (DPDP) Act, 2023**

---

## 3. Scope

### 3.1 In Scope

The following are within the scope of this BRD and the A2A-CRM module:

- Enquiry and lead capture from all digital and offline channels
- Lead qualification, scoring and nurturing workflows
- Counsellor and counselling centre management
- Application form management and admission pipeline tracking
- Multi-channel communication (Email, SMS, WhatsApp, Voice, IVR)
- Marketing campaign management and automation
- Fee collection, scholarships and payment gateway integration
- Document collection, verification and tracking
- Task management, follow-ups and activity logging
- Telecalling / call centre functionality
- Student self-service portal (pre-admission)
- Channel partner and agent management
- Transition bridge to A2A post-admission modules (Alumni, Academics, HR)
- AI-powered lead scoring, next best action, and agentic proposal/communication drafting
- Analytics, dashboards and MIS reports
- Mobile application (iOS and Android)
- Native A2A ERP integration
- Third-party integration APIs

### 3.2 Out of Scope (Current Version)

- Post-admission academic management (covered by A2A Academics module)
- HRMS functions (covered by TalenTicks)
- Recruitment of staff/faculty (covered by BriskHire)
- International/overseas student visa processing
- Learning Management (covered by CamPLUS / Moodle)

---

## 4. Stakeholders

| Stakeholder Group | Role | Interest / Influence |
|---|---|---|
| MEETCS Management | Product Owner, Sponsor | Strategic direction, investment decisions |
| A2A Product Team | Requirements, Design, Build | Feature specification and delivery |
| Client — Admissions Head | Primary User | Enrolment targets, counsellor productivity |
| Client — Marketing Team | Primary User | Campaign execution, lead cost management |
| Client — Principal / Director | Decision Maker | Institution-level dashboards and ROI |
| Client — Counsellors | Day-to-day Users | Lead follow-up, communication, conversion |
| Client — Finance / Accounts | User | Fee management, payment reconciliation |
| Client — IT / System Admin | Configurator | System setup, integrations, user management |
| Prospective Students | End Beneficiaries | Application experience, communication quality |
| Channel Partners / Agents | External Users | Lead submission, commission tracking |
| MEETCS Dev Team | Builders | Technical feasibility, implementation |

---

## 5. Business Objectives and Success Metrics

### 5.1 Business Objectives

| ID | Objective |
|---|---|
| BO-01 | Provide institutions with a single, unified platform for the complete pre-admission lifecycle |
| BO-02 | Reduce lead-to-enrolment conversion time by a minimum of 20% versus manual/fragmented processes |
| BO-03 | Eliminate data re-entry between CRM and ERP through native integration |
| BO-04 | Enable data-driven admissions through real-time analytics and AI-assisted decision support |
| BO-05 | Ensure full compliance with DPDP Act 2023 for all student personal data handling |
| BO-06 | Support multi-institution, multi-campus deployments with role-based access and data segregation |
| BO-07 | Deliver a mobile-first experience for counsellors and admissions teams in the field |

### 5.2 Key Performance Indicators

| KPI | Target |
|---|---|
| Lead Capture Rate (enquiries auto-logged vs manual) | ≥ 95% |
| Lead Response Time (first counsellor contact after enquiry) | ≤ 30 minutes |
| Counsellor Productivity (enquiries handled per day per counsellor) | +30% vs baseline |
| Application Completion Rate | ≥ 70% of started applications |
| Enquiry-to-Enrolment Conversion Rate | Benchmark set at Go-Live; 15% improvement Year 1 |
| System Uptime | 99.5% excluding planned maintenance |
| Data Migration Accuracy (from legacy systems) | ≥ 99% |

---

## 6. Competitive Landscape Analysis

The following analysis informs the feature depth and differentiation strategy for A2A-CRM.

### 6.1 Competitor Feature Summary

| Feature Area | Meritto | LeadSquared | ExtraAedge | Mastersoft | Salesforce Edu Cloud | **A2A-CRM Target** |
|---|---|---|---|---|---|---|
| Education-specific workflows | ✅ Native | ⚠️ Configurable | ✅ Native | ✅ Native | ⚠️ Configurable | ✅ **Native + ERP-integrated** |
| Lead Capture — Omnichannel | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| AI Lead Scoring | ⚠️ Basic | ✅ | ✅ Data Science | ⚠️ | ✅ Einstein | ✅ **Agentic AI (Anthropic)** |
| WhatsApp Automation | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ |
| Application Form Builder | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Fee & Payment Management | ✅ | ⚠️ | ⚠️ | ✅ | ⚠️ | ✅ **Native A2A Fee Engine** |
| Post-Admission ERP Integration | ❌ | ❌ | ❌ | ⚠️ Partial | ⚠️ Add-on | ✅ **Seamless Native** |
| Alumni Lifecycle Bridge | ❌ | ❌ | ❌ | ❌ | ⚠️ | ✅ **A2A Alumni Module** |
| HRMS Integration | ❌ | ❌ | ❌ | ❌ | ⚠️ | ✅ **TalenTicks Native** |
| LMS Integration | ❌ | ❌ | ❌ | ⚠️ | ⚠️ | ✅ **CamPLUS / Moodle** |
| DPDP Act Compliance (India) | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ✅ **Built-in** |
| Pricing Model | Per application | Subscription | Subscription | Licence | Enterprise | Flexible — Licence + SaaS |
| Mobile App | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ |
| Telecalling / IVR | ⚠️ | ✅ | ✅ | ⚠️ | ⚠️ | ✅ |
| Agent / Channel Partner Portal | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ |
| Gamification | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ **TalenTicks DNA** |

### 6.2 Key Differentiators for A2A-CRM

1. **True Admissions-to-Alumni Continuity** — No competitor offers a CRM where a lead record seamlessly becomes a student record and then an alumni record within the same platform without data migration or manual mapping.
2. **Agentic AI Layer** — Built on Anthropic's API, A2A-CRM embeds AI for lead scoring, next best action, automated communication drafting, and tender/proposal intelligence — capabilities that competitors are only beginning to explore.
3. **Native Fee Engine** — Fee structures, scholarship slabs and payment plans from the A2A Fee module are available directly in the CRM without re-configuration.
4. **DPDP-First Architecture** — Consent management, data minimisation, and right-to-erasure workflows built into the core data model.
5. **Gamification** — Counsellor performance gamification drawing from TalenTicks HRMS DNA, creating engagement and healthy competition within admissions teams.

---

## 7. Assumptions, Constraints and Dependencies

### 7.1 Assumptions

| ID | Assumption |
|---|---|
| A-01 | The A2A-CRM will be deployed on the existing A2A ERP infrastructure (Laravel + Vue.js) |
| A-02 | All client institutions will have a minimum of 10 Mbps internet connectivity |
| A-03 | WhatsApp Business API access will be provisioned through an approved BSP (Business Solution Provider) |
| A-04 | SMS and IVR gateway integrations will be made available via third-party API (e.g., MSG91, Exotel) |
| A-05 | AI capabilities will be powered by the Anthropic Claude API (existing MEETCS integration) |
| A-06 | Payment gateway integrations will cover Razorpay, PayU and CCAvenue as minimum baseline |
| A-07 | DPDP Act compliance framework developed for GIM (Goa Institute of Management) will serve as the baseline |
| A-08 | Mobile app will be built using a cross-platform framework (React Native or Flutter) |

### 7.2 Constraints

| ID | Constraint |
|---|---|
| C-01 | Module must remain backward compatible with existing A2A ERP deployments (40+ live instances) |
| C-02 | No hard dependency on third-party CRM licences for core functionality |
| C-03 | All personal data of Indian students must be stored on servers within India (DPDP Act) |
| C-04 | Initial release must support concurrent usage by a minimum of 500 counsellors per institution |
| C-05 | Development team constraints apply to phased delivery; full feature set is not a single-phase release |

### 7.3 Dependencies

| ID | Dependency | Owner |
|---|---|---|
| D-01 | A2A Student Master data model (for post-admission integration) | MEETCS Dev |
| D-02 | A2A Fee and Finance module API | MEETCS Dev |
| D-03 | Anthropic API (AI features) | Anthropic / MEETCS |
| D-04 | WhatsApp BSP agreement | MEETCS / Client |
| D-05 | Payment gateway onboarding | Client / Finance |
| D-06 | SMS and IVR gateway credentials | Client / IT |
| D-07 | API Setu for Aadhaar/DigiLocker document verification | MeitY / MEETCS |

---

## 8. Functional Requirements

---

### 8.1 Lead Capture and Source Management

**Purpose:** Capture enquiries from every possible touchpoint — online and offline — into a single, de-duplicated lead database with full source attribution.

#### 8.1.1 Web and Digital Lead Capture

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-LC-001 | System shall provide embeddable, customisable web enquiry forms (iFrame and native) for institution websites | Must Have |
| CRM-LC-002 | Forms shall support conditional field logic (show/hide fields based on prior answers) | Must Have |
| CRM-LC-003 | Leads from Google Ads (via Google Lead Form Extensions) shall be auto-imported via webhook | Must Have |
| CRM-LC-004 | Leads from Facebook/Instagram Lead Ads shall be auto-imported via Meta Lead Ads API | Must Have |
| CRM-LC-005 | System shall support lead capture from landing pages built within the CRM (drag-and-drop page builder) | Should Have |
| CRM-LC-006 | Live chat widget shall be available for embedding on institution websites, with enquiries creating CRM leads | Should Have |
| CRM-LC-007 | WhatsApp Click-to-Chat links shall auto-create lead records when a prospective student initiates a conversation | Must Have |
| CRM-LC-008 | Leads from third-party education portals (Shiksha, CollegeDekho, Careers360, etc.) shall be importable via API or CSV | Must Have |
| CRM-LC-009 | System shall support QR-code-based lead capture for walk-in enquiries and events | Must Have |

#### 8.1.2 Telephony and Offline Lead Capture

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-LC-010 | Inbound calls to a virtual number shall automatically create lead records with caller details and call recording | Must Have |
| CRM-LC-011 | Counsellors shall be able to manually create leads via desktop and mobile interfaces | Must Have |
| CRM-LC-012 | Bulk lead upload via Excel/CSV shall be supported with a validation and error report | Must Have |
| CRM-LC-013 | Walk-in enquiry registration shall be available via a dedicated kiosk-friendly interface | Should Have |

#### 8.1.3 Lead Source Tracking and Attribution

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-LC-014 | Every lead shall carry a mandatory Source field (e.g., Google Ads, Facebook, Walk-in, Referral, Education Portal, WhatsApp, IVR, Event, Website Organic) | Must Have |
| CRM-LC-015 | UTM parameter tracking shall be supported for all web form and landing page submissions | Must Have |
| CRM-LC-016 | Multi-touch attribution model shall be available (first touch, last touch, linear, configurable) | Should Have |
| CRM-LC-017 | Cost-per-lead tracking shall be available by linking campaign spends to lead source | Should Have |

#### 8.1.4 De-duplication and Lead Merging

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-LC-018 | System shall auto-detect duplicate leads based on mobile number, email or name+course combination and flag or auto-merge per configured rules | Must Have |
| CRM-LC-019 | Admins shall be able to manually merge duplicate lead records, with full activity history preserved | Must Have |
| CRM-LC-020 | Leads that match an existing admitted student or alumni record in A2A ERP shall be flagged and linked | Must Have |

---

### 8.2 Lead Qualification and Scoring

**Purpose:** Rank and segment leads by conversion probability to enable counsellors to prioritise high-value prospects and trigger automated nurturing for lower-priority leads.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-LQ-001 | System shall provide a configurable, rule-based lead scoring engine with a score range of 0–100 | Must Have |
| CRM-LQ-002 | Scoring parameters shall include: demographic data completeness, course interest match, engagement activity (email opens, WhatsApp reads, form revisits), response time to counsellor, and geographic proximity | Must Have |
| CRM-LQ-003 | AI-assisted lead scoring shall augment rule-based scores using historical conversion pattern analysis | Should Have |
| CRM-LQ-004 | Lead score shall be recalculated in real-time on every qualifying activity | Must Have |
| CRM-LQ-005 | System shall support lead temperature classification: Hot / Warm / Cold / Lost / Converted, configurable per institution | Must Have |
| CRM-LQ-006 | Score thresholds shall trigger automated workflow actions (e.g., Hot lead → immediate counsellor alert; Cold lead → enter nurture drip sequence) | Must Have |
| CRM-LQ-007 | Counsellors shall be able to manually override the AI/rule-based score with a documented reason | Must Have |
| CRM-LQ-008 | Lead quality grading by source shall be reportable (e.g., Google Ads leads vs. Referral leads by conversion rate) | Must Have |
| CRM-LQ-009 | System shall support custom qualification questionnaires (BANT-equivalent for education: Budget, Awareness, Need, Timeline) | Should Have |
| CRM-LQ-010 | Predictive churn flag shall identify leads at risk of dropping off based on inactivity thresholds | Should Have |

---

### 8.3 Enquiry and Counselling Management

**Purpose:** Manage the end-to-end counselling interaction, from initial enquiry through to programme selection and application submission.

#### 8.3.1 Lead / Enquiry Record

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-EC-001 | A lead/enquiry record shall capture: full name, contact details, academic background (10th/12th/graduation marks, board/university), course(s) of interest, preferred batch/intake, current status, assigned counsellor, source, score, notes, and all activity history | Must Have |
| CRM-EC-002 | A lead may be associated with one or more programmes of interest with individual status tracking per programme | Must Have |
| CRM-EC-003 | Counsellor notes shall support rich text, tagging, and timestamp | Must Have |
| CRM-EC-004 | Complete activity timeline (calls, emails, WhatsApp, SMS, notes, status changes, document uploads, payment events) shall be displayed chronologically on the lead record | Must Have |
| CRM-EC-005 | Lead records shall support custom fields configurable per institution | Must Have |

#### 8.3.2 Counsellor Assignment and Workload Management

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-EC-006 | Leads shall be auto-assigned to counsellors based on configurable rules: round-robin, workload balancing, geography, programme specialisation, or counsellor capacity | Must Have |
| CRM-EC-007 | Admissions Manager shall be able to manually reassign leads | Must Have |
| CRM-EC-008 | Counsellor workload dashboard shall display current active leads, follow-ups due, applications pending, and conversion rate | Must Have |
| CRM-EC-009 | Lead escalation rules shall auto-escalate unactioned leads after a configurable time threshold | Must Have |
| CRM-EC-010 | Counsellor performance scoring (leads handled, conversion rate, response time, student satisfaction) shall be tracked and gamified | Should Have |

#### 8.3.3 Enquiry Status Workflow

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-EC-011 | System shall support a configurable enquiry status pipeline with stages such as: New Enquiry → Contacted → Counselling Scheduled → Counselling Done → Application Started → Application Submitted → Offer Letter Issued → Fee Paid → Enrolled → Deferred → Lost | Must Have |
| CRM-EC-012 | Status transitions shall trigger configured automation actions (notifications, tasks, communications) | Must Have |
| CRM-EC-013 | Reasons for loss/dropout shall be captured on status change to "Lost" (mandatory dropdown) | Must Have |
| CRM-EC-014 | Historical status journey shall be preserved and reportable for each lead | Must Have |

#### 8.3.4 Appointment and Slot Booking

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-EC-015 | Counsellors shall have calendar availability management with time-slot blocking | Must Have |
| CRM-EC-016 | Prospective students shall be able to book a counselling appointment online via the student portal or a shared booking link | Must Have |
| CRM-EC-017 | System shall send automated appointment reminders (email, SMS, WhatsApp) to both student and counsellor | Must Have |
| CRM-EC-018 | Video counselling shall be supported via embedded integration (Zoom, Google Meet, or native WebRTC) | Should Have |
| CRM-EC-019 | Walk-in queue management shall support token-based system for in-person counselling centres | Should Have |

---

### 8.4 Application and Admission Pipeline Management

**Purpose:** Manage the formal application process, document collection, merit evaluation, offer issuance, and fee confirmation, with direct handoff to the A2A ERP student record.

#### 8.4.1 Online Application Form

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AP-001 | System shall provide a configurable, multi-step online application form builder with section, field and logic configuration | Must Have |
| CRM-AP-002 | Application forms shall support: personal details, academic history, entrance exam scores, co-curricular activities, declarations, and digital signature | Must Have |
| CRM-AP-003 | Application form shall support save-and-resume functionality | Must Have |
| CRM-AP-004 | Applicants shall be able to pay a configurable application fee online at the time of submission | Must Have |
| CRM-AP-005 | System shall support simultaneous applications to multiple programmes within the same institution | Must Have |
| CRM-AP-006 | Application form shall be fully responsive and mobile-optimised | Must Have |
| CRM-AP-007 | Institution shall be able to define mandatory vs. optional fields and minimum completeness thresholds | Must Have |

#### 8.4.2 Admission Pipeline (Kanban and List Views)

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AP-008 | A visual Kanban pipeline board shall display applications across configurable stages | Must Have |
| CRM-AP-009 | Applications shall be filterable by programme, batch, counsellor, source, status, date range and score | Must Have |
| CRM-AP-010 | Bulk actions shall be available: send communication, update status, assign counsellor, export | Must Have |
| CRM-AP-011 | Programme-wise seat availability vs. application count shall be displayed in real time | Must Have |

#### 8.4.3 Offer Letter and Admission Confirmation

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AP-012 | System shall generate customisable, digitally signed offer letters and admission confirmation letters as PDF | Must Have |
| CRM-AP-013 | Offer letter delivery shall be via email, WhatsApp and downloadable from student portal | Must Have |
| CRM-AP-014 | Conditional offer management (pending document submission or qualifying exam result) shall be supported | Should Have |
| CRM-AP-015 | Offer acceptance tracking and digital confirmation by applicant shall be captured | Must Have |

#### 8.4.4 ERP Handoff — Lead to Student Conversion

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AP-016 | Upon fee confirmation and enrolment completion, a single-click conversion of the CRM lead/applicant record into an A2A ERP Student Master record shall be available | Must Have |
| CRM-AP-017 | All CRM data (personal details, academic history, documents, communication history) shall be inherited by the ERP student record — zero re-entry | Must Have |
| CRM-AP-018 | Conversion event shall trigger onboarding workflows in A2A ERP (ID card generation, LMS enrolment, hostel allocation prompt, etc.) | Should Have |
| CRM-AP-019 | Conversion rate reporting (applications → enrolled) shall be available by programme, batch, source and counsellor | Must Have |

---

### 8.5 Multi-Channel Communication Engine

**Purpose:** Enable omnichannel, personalised communication with prospective students across all touchpoints, with full delivery tracking and activity logging.

#### 8.5.1 Email Communication

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-CC-001 | System shall provide a drag-and-drop email template builder with merge tags for personalisation | Must Have |
| CRM-CC-002 | Emails shall be sendable individually (from lead record) or in bulk (campaign or segment) | Must Have |
| CRM-CC-003 | Email delivery, open, click, bounce and unsubscribe events shall be tracked and logged to the lead record | Must Have |
| CRM-CC-004 | Institution shall be able to configure a custom sender domain (SPF/DKIM/DMARC support) | Must Have |
| CRM-CC-005 | Unsubscribe management shall be DPDP Act compliant (opt-out respected and logged) | Must Have |

#### 8.5.2 SMS Communication

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-CC-006 | SMS shall be sendable to individual leads or in bulk with personalisation | Must Have |
| CRM-CC-007 | Integration with major Indian SMS gateways (MSG91, Textlocal, Kaleyra) via configurable API | Must Have |
| CRM-CC-008 | DLT (Distributed Ledger Technology) template registration workflow shall be built into the SMS module | Must Have |
| CRM-CC-009 | SMS delivery status shall be tracked and logged | Must Have |

#### 8.5.3 WhatsApp Communication

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-CC-010 | Integration with WhatsApp Business API via approved BSP (Business Solution Provider) | Must Have |
| CRM-CC-011 | System shall support template-based WhatsApp messages for transactional triggers (application received, offer letter, fee reminder) | Must Have |
| CRM-CC-012 | Counsellors shall be able to send and receive WhatsApp messages from within the CRM lead record (shared inbox) | Must Have |
| CRM-CC-013 | WhatsApp chatbot shall handle initial FAQs, programme information, brochure delivery, and appointment booking | Should Have |
| CRM-CC-014 | WhatsApp message delivery, read and reply events shall be tracked | Must Have |
| CRM-CC-015 | Bulk WhatsApp broadcasts to segmented lead lists shall be supported within BSP policy limits | Must Have |

#### 8.5.4 Voice and IVR

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-CC-016 | Integration with cloud telephony providers (Exotel, Ozonetel, Knowlarity) for outbound calling | Must Have |
| CRM-CC-017 | Click-to-call shall be available from the lead record for counsellors | Must Have |
| CRM-CC-018 | All calls shall be logged automatically with duration, disposition and recording (where consented) | Must Have |
| CRM-CC-019 | IVR (Interactive Voice Response) for inbound enquiries shall be configurable with lead capture | Should Have |
| CRM-CC-020 | Missed call campaigns (missed-call-to-lead-creation) shall be supported | Should Have |

#### 8.5.5 Communication Inbox and Unified Timeline

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-CC-021 | A unified communication inbox shall consolidate all inbound messages (email, WhatsApp, chat) for a counsellor's assigned leads | Must Have |
| CRM-CC-022 | All outbound and inbound communications shall appear in the lead's activity timeline in chronological order | Must Have |
| CRM-CC-023 | Counsellors shall be notified (in-app, email, mobile push) on new inbound message receipt | Must Have |

---

### 8.6 Marketing Automation

**Purpose:** Automate lead nurturing, engagement sequences and campaign execution to improve conversion rates and reduce counsellor manual effort.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-MA-001 | Visual workflow builder (drag-and-drop) shall enable creation of multi-step automation sequences with triggers, conditions and actions | Must Have |
| CRM-MA-002 | Trigger types shall include: lead created, form submitted, email opened, link clicked, lead score changed, status changed, date/time based (e.g., 7 days before application deadline), and inactivity timeout | Must Have |
| CRM-MA-003 | Action types shall include: send email, send SMS, send WhatsApp message, assign counsellor, update lead field, add tag, create task, enrol in another workflow, webhook call | Must Have |
| CRM-MA-004 | A/B testing for email subject lines and message content in automated sequences shall be supported | Should Have |
| CRM-MA-005 | Drip campaign sequences shall support configurable time delays between steps | Must Have |
| CRM-MA-006 | Leads shall be automatically removed from nurture sequences upon status change to "Contacted" or higher | Must Have |
| CRM-MA-007 | System shall support re-engagement sequences for cold/inactive leads | Should Have |
| CRM-MA-008 | Programme-specific nurture journeys (e.g., MBA, B.Tech, MBA-Executive) shall be configurable separately | Must Have |
| CRM-MA-009 | Event-based automation shall support: Open Day invitations, webinar reminders, result announcement, admission deadline reminders | Must Have |
| CRM-MA-010 | Automation performance reporting (sequence-level open rates, click-through rates, conversion attribution) shall be available | Must Have |

---

### 8.7 Fee, Scholarship and Payment Management

**Purpose:** Manage all pre-admission financial transactions within the CRM, with direct integration into the A2A Fee module for post-admission continuity.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-FM-001 | System shall support online application fee collection with configurable amounts per programme | Must Have |
| CRM-FM-002 | Seat reservation / booking fee (partial advance fee) collection shall be supported | Must Have |
| CRM-FM-003 | Integration with payment gateways (Razorpay, PayU, CCAvenue) shall be available | Must Have |
| CRM-FM-004 | Payment links shall be generatable and shareable via WhatsApp, SMS, and email from the lead record | Must Have |
| CRM-FM-005 | Payment confirmation shall be auto-logged to the lead record and trigger status update | Must Have |
| CRM-FM-006 | Configurable scholarship and fee waiver categories (merit, sports, management quota, early bird, sibling) shall be manageable within the CRM | Must Have |
| CRM-FM-007 | Scholarship eligibility evaluation against defined criteria shall be automatable | Should Have |
| CRM-FM-008 | Fee discount and waiver approval workflow (counsellor → manager → finance head) shall be supported | Must Have |
| CRM-FM-009 | Payment instalment plans for initial fee shall be configurable | Should Have |
| CRM-FM-010 | Automated payment reminders (via WhatsApp, SMS, email) before due dates shall be supported | Must Have |
| CRM-FM-011 | Fee refund request workflow shall be initiated from CRM for applicants who withdraw | Should Have |
| CRM-FM-012 | Financial dashboards shall show: total fees collected, pending collections, refunds, scholarship impact, and revenue forecast by programme | Must Have |
| CRM-FM-013 | Upon enrolment conversion, CRM fee records shall be migrated to the A2A ERP Fee module — no re-entry | Must Have |

---

### 8.8 Document Management

**Purpose:** Collect, track and verify all documents required during the admissions process, with API Setu integration for Aadhaar/DigiLocker verification.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-DM-001 | Configurable document checklists per programme (e.g., 10th marksheet, 12th marksheet, ID proof, caste certificate, entrance exam scorecard) shall be definable | Must Have |
| CRM-DM-002 | Applicants shall be able to upload documents via student portal, WhatsApp bot, or email | Must Have |
| CRM-DM-003 | Document status tracking (Not Submitted / Submitted / Under Review / Verified / Rejected) shall be maintained per document per applicant | Must Have |
| CRM-DM-004 | Admissions staff shall be able to verify, approve or reject documents with comments | Must Have |
| CRM-DM-005 | Automated reminders shall be sent for pending document submissions | Must Have |
| CRM-DM-006 | Integration with DigiLocker via API Setu shall enable applicants to share verified documents directly | Should Have |
| CRM-DM-007 | Aadhaar-based identity verification via API Setu shall be supported (OTP-based eKYC) | Should Have |
| CRM-DM-008 | Document storage shall be encrypted at rest and access-controlled by role | Must Have |
| CRM-DM-009 | Bulk document download (per applicant or per programme batch) shall be available for admissions staff | Must Have |
| CRM-DM-010 | Document completeness score per applicant shall be visible on the lead record | Must Have |

---

### 8.9 Task, Activity and Follow-Up Management

**Purpose:** Ensure counsellors never miss a follow-up, and managers have full visibility into team activity.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-TF-001 | Counsellors shall be able to create tasks (call, email, WhatsApp, meeting, document review) linked to a lead | Must Have |
| CRM-TF-002 | System shall auto-create follow-up tasks based on configured rules (e.g., no contact in 3 days → auto-create call task) | Must Have |
| CRM-TF-003 | Counsellor's daily task list shall be displayed on their dashboard, sorted by priority and due time | Must Have |
| CRM-TF-004 | Overdue tasks shall be flagged prominently and escalated per configured rules | Must Have |
| CRM-TF-005 | Task completion shall require a disposition/outcome (e.g., Reached-Interested, Reached-Not Interested, Not Reachable, Call Back Requested) | Must Have |
| CRM-TF-006 | Managers shall have a team-level task and activity view | Must Have |
| CRM-TF-007 | Activity feed (what each counsellor has done today) shall be available to managers in real time | Must Have |
| CRM-TF-008 | Bulk task assignment and reassignment shall be available | Must Have |
| CRM-TF-009 | Calendar view of tasks (daily, weekly, monthly) shall be available for counsellors | Must Have |

---

### 8.10 Telecalling and Call Centre Module

**Purpose:** Enable high-volume outbound calling campaigns and structured inbound call management for large admissions teams.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-TC-001 | Power/auto-dialler capability shall enable counsellors to work through calling lists efficiently | Should Have |
| CRM-TC-002 | Call scripts shall be displayable to counsellors during an active call, with branching based on responses | Should Have |
| CRM-TC-003 | Call dispositions shall be configurable (Interested, Not Interested, Call Back, Wrong Number, Not Reachable, Number Invalid, etc.) | Must Have |
| CRM-TC-004 | Post-call, the system shall automatically prompt the counsellor to schedule the next follow-up | Must Have |
| CRM-TC-005 | Supervisor/manager shall be able to listen to live calls (barge-in), whisper to counsellors, or take over calls | Should Have |
| CRM-TC-006 | Calling campaign management (define list, assign agents, set time window, track progress) shall be available | Must Have |
| CRM-TC-007 | Call centre performance dashboard (calls made, talk time, connects, conversions per agent) shall be available | Must Have |
| CRM-TC-008 | Automatic call recording with storage, playback and search shall be supported (with DPDP consent compliance) | Must Have |
| CRM-TC-009 | Do-Not-Call (DNC) list management shall be built in | Must Have |

---

### 8.11 Student Portal and Self-Service

**Purpose:** Provide applicants with a branded, self-service portal to track application status, upload documents, make payments, book counselling appointments, and communicate with the institution.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-SP-001 | A branded, mobile-responsive applicant portal shall be configurable per institution | Must Have |
| CRM-SP-002 | Applicants shall authenticate via OTP (mobile and/or email) | Must Have |
| CRM-SP-003 | Portal shall display: application status, document checklist, payment history, communication history, and upcoming appointments | Must Have |
| CRM-SP-004 | Applicants shall be able to initiate chat with their assigned counsellor from the portal | Should Have |
| CRM-SP-005 | Offer letter, admission confirmation letter and payment receipts shall be downloadable from the portal | Must Have |
| CRM-SP-006 | Portal shall support multiple simultaneous applications (for students applying to multiple programmes) | Must Have |
| CRM-SP-007 | Upon enrolment, the applicant portal shall seamlessly transition to the A2A Student ERP portal — same credentials, no re-registration | Must Have |
| CRM-SP-008 | Portal shall support institutional branding (logo, colours, domain) | Must Have |

---

### 8.12 Agent and Channel Partner Management

**Purpose:** Manage channel partners, agents, and referral sources that bring leads to the institution, including commission tracking.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AG-001 | Agent/channel partner profiles shall be maintainable with contact details, agreement terms, active programmes, and performance history | Must Have |
| CRM-AG-002 | Each agent shall have a unique referral link/code for lead attribution | Must Have |
| CRM-AG-003 | Agent portal shall allow partners to submit leads, track lead status, and view their conversion dashboard | Must Have |
| CRM-AG-004 | Commission structures (per enrolled student, per application, percentage of fee) shall be configurable per agent agreement | Must Have |
| CRM-AG-005 | Commission accrual shall be auto-calculated on enrolment confirmation | Must Have |
| CRM-AG-006 | Commission approval and payout workflow shall be available | Should Have |
| CRM-AG-007 | Agent performance report (leads submitted, conversions, revenue generated, commissions) shall be available | Must Have |
| CRM-AG-008 | Communication tools (bulk email/WhatsApp to agent network) shall be available | Should Have |

---

### 8.13 Alumni Lifecycle Bridge

**Purpose:** Ensure seamless continuity from the CRM to the A2A Alumni module, enabling alumni engagement to feed back as referral leads, and leveraging alumni as a brand ambassador channel.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AL-001 | Enrolled student records converted to A2A ERP shall automatically populate the future alumni pipeline upon programme completion | Must Have |
| CRM-AL-002 | Alumni referral campaigns shall generate unique referral codes for alumni to share with prospective students | Should Have |
| CRM-AL-003 | Leads generated via alumni referral shall be tagged and tracked for conversion, enabling alumni reward programmes | Should Have |
| CRM-AL-004 | Alumni satisfaction data (NPS scores from A2A Alumni module) shall be surfaceable on the CRM analytics dashboard as a lead conversion influencer | Could Have |

---

### 8.14 Analytics, Dashboards and Reporting

**Purpose:** Provide role-appropriate, real-time visibility into the admissions funnel, counsellor performance, campaign effectiveness, and revenue pipeline.

#### 8.14.1 Dashboards

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AR-001 | Institution-level admissions dashboard: total leads, applications, offers, enrolments, revenue — by programme, campus, source, and period | Must Have |
| CRM-AR-002 | Counsellor performance dashboard: leads owned, tasks completed, conversion rate, average response time | Must Have |
| CRM-AR-003 | Marketing campaign dashboard: spend vs. leads, cost per lead, cost per enrolment, channel ROI | Must Have |
| CRM-AR-004 | Admissions funnel visualisation (stage-wise conversion rates with drop-off analysis) | Must Have |
| CRM-AR-005 | Real-time seat availability vs. confirmed enrolments dashboard by programme and batch | Must Have |
| CRM-AR-006 | Management / Director-level executive dashboard with KPI tiles and trend charts | Must Have |
| CRM-AR-007 | Dashboards shall be role-based (counsellor sees own data; manager sees team; director sees institution) | Must Have |
| CRM-AR-008 | All dashboards shall support date range filtering and drill-down to individual lead records | Must Have |

#### 8.14.2 Standard Reports

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AR-009 | Enquiry Register Report (all leads with source, status, assigned counsellor, last contact date) | Must Have |
| CRM-AR-010 | Counsellor Activity Report (daily/weekly activity summary per counsellor) | Must Have |
| CRM-AR-011 | Application Status Report (applications by stage, programme, counsellor) | Must Have |
| CRM-AR-012 | Source Effectiveness Report (leads, applications, enrolments, revenue by source) | Must Have |
| CRM-AR-013 | Lost Lead Analysis Report (drop-off stage and reason) | Must Have |
| CRM-AR-014 | Fee Collection Report (collected, pending, overdue, by programme) | Must Have |
| CRM-AR-015 | Document Compliance Report (applicants with pending documents by programme) | Must Have |
| CRM-AR-016 | Year-on-Year Comparison Report (leads, applications, enrolments vs. prior academic year) | Must Have |
| CRM-AR-017 | Agent Performance Report | Must Have |

#### 8.14.3 Custom Reports and Export

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AR-018 | Custom report builder shall allow users to select fields, filters, grouping and aggregations | Should Have |
| CRM-AR-019 | All reports shall be exportable to Excel and PDF | Must Have |
| CRM-AR-020 | Scheduled report delivery via email shall be configurable | Should Have |
| CRM-AR-021 | API access to analytics data for Power BI / Tableau integration shall be available | Could Have |

---

### 8.15 AI and Agentic Intelligence Layer

**Purpose:** Embed AI-driven intelligence throughout the CRM to augment counsellor productivity, improve lead conversion, and enable proactive admissions management. Built on the Anthropic Claude API, leveraging MEETCS's existing AI CRM engine.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-AI-001 | AI-assisted lead scoring shall analyse lead behaviour patterns and historical conversion data to predict conversion probability | Must Have |
| CRM-AI-002 | Next Best Action engine shall recommend the optimal next counsellor action for each lead (call, WhatsApp, email, invite to event) with reasoning | Must Have |
| CRM-AI-003 | AI-assisted communication drafting shall generate personalised email/WhatsApp message drafts in the context of the lead's profile and stage | Must Have |
| CRM-AI-004 | Sentiment analysis on inbound emails and chat messages shall flag negative or urgent leads for priority attention | Should Have |
| CRM-AI-005 | AI shall generate a daily priority lead list for each counsellor based on score, inactivity, and conversion probability | Must Have |
| CRM-AI-006 | Conversational AI (chatbot) for WhatsApp and web chat shall handle Tier-1 enquiries (programme info, eligibility, fee structure, application process) and escalate to human counsellors when needed | Should Have |
| CRM-AI-007 | AI-powered call transcription and summary generation (post-call) shall be available | Could Have |
| CRM-AI-008 | Predictive enrolment forecasting (projected enrolments by programme for current cycle) shall be available on the analytics dashboard | Should Have |
| CRM-AI-009 | Anomaly detection shall flag unusual drops in enquiry volume or conversion rates and alert admissions managers | Should Have |
| CRM-AI-010 | AI shall assist in building segment-specific nurture journeys by recommending optimal message timing, channel, and content based on cohort behaviour | Should Have |
| CRM-AI-011 | All AI-generated content and recommendations shall be presented as suggestions; final action shall remain with the human user | Must Have |
| CRM-AI-012 | AI usage logs shall be maintained for audit and DPDP compliance purposes | Must Have |

---

### 8.16 Mobile Application

**Purpose:** Enable counsellors and admissions managers to manage leads, tasks and communications on-the-go via a dedicated mobile application.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-MB-001 | Native mobile app shall be available for iOS and Android | Must Have |
| CRM-MB-002 | App shall support: lead view, lead status update, task management, click-to-call, WhatsApp messaging, note logging | Must Have |
| CRM-MB-003 | Push notifications shall be delivered for: new lead assigned, follow-up due, inbound message received, escalation alert | Must Have |
| CRM-MB-004 | Business card / visiting card scanner shall create a new lead record via OCR | Should Have |
| CRM-MB-005 | QR code scanner for walk-in enquiry capture shall be available on the mobile app | Must Have |
| CRM-MB-006 | App shall function in offline mode for viewing and note-taking, with sync on reconnect | Should Have |
| CRM-MB-007 | App shall support biometric authentication (fingerprint/face unlock) | Should Have |
| CRM-MB-008 | Manager-level app view shall include team dashboards and activity feeds | Must Have |

---

### 8.17 ERP Integration Layer (A2A Native)

**Purpose:** Define the native integration points between A2A-CRM and the broader A2A ERP ecosystem, ensuring seamless data continuity across the student lifecycle.

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-EI-001 | Programme Master from A2A ERP shall be synchronised to CRM (programme name, code, duration, intake capacity, eligibility criteria) | Must Have |
| CRM-EI-002 | Fee structure from A2A Fee module shall be accessible within CRM for display to applicants and counsellors without re-configuration | Must Have |
| CRM-EI-003 | Academic Calendar (intake dates, application deadlines, orientation dates) from A2A ERP shall be visible in CRM | Must Have |
| CRM-EI-004 | Seat availability (sanctioned intake vs. confirmed enrolments) shall reflect live A2A ERP data | Must Have |
| CRM-EI-005 | Lead-to-Student conversion shall write to the A2A Student Master with full field mapping | Must Have |
| CRM-EI-006 | CRM payments shall be reflected in A2A Fee module accounts (pre-admission fee accounting) | Must Have |
| CRM-EI-007 | CRM documents shall be accessible from the A2A Student record post-conversion | Must Have |
| CRM-EI-008 | A2A Alumni module shall receive graduate records, enabling alumni engagement and referral programme | Should Have |
| CRM-EI-009 | TalenTicks HRMS shall receive counsellor activity data for HR performance review integration | Could Have |
| CRM-EI-010 | CamPLUS / Moodle LMS enrolment shall be auto-triggered for enrolled students upon CRM-to-ERP conversion | Should Have |

---

### 8.18 Third-Party Integrations

| Integration Category | Specific Integrations | Priority |
|---|---|---|
| Email Gateways | SMTP (custom), SendGrid, AWS SES | Must Have |
| SMS Gateways | MSG91, Textlocal, Kaleyra, Exotel SMS | Must Have |
| WhatsApp BSP | Interakt, Wati, 360dialog, Gupshup | Must Have |
| Telephony / IVR | Exotel, Ozonetel, Knowlarity, Tata Tele | Must Have |
| Payment Gateways | Razorpay, PayU, CCAvenue | Must Have |
| Education Portals | Shiksha, CollegeDekho, Careers360, Collegedunia (lead import API) | Must Have |
| Social / Ad Platforms | Meta Lead Ads, Google Lead Form Extensions | Must Have |
| Document Verification | API Setu — DigiLocker, Aadhaar eKYC | Should Have |
| Video Conferencing | Zoom, Google Meet | Should Have |
| Cloud Storage | AWS S3, Google Cloud Storage (document hosting) | Must Have |
| Analytics / BI | Power BI, Google Data Studio (data export API) | Could Have |
| ERP (External) | REST API for non-A2A ERP integration (for standalone CRM licensing) | Should Have |
| AI / LLM | Anthropic Claude API | Must Have |

---

### 8.19 System Administration and Configuration

| Req ID | Requirement | Priority |
|---|---|---|
| CRM-SA-001 | Multi-institution support: A single A2A-CRM deployment shall support multiple institutions with complete data segregation | Must Have |
| CRM-SA-002 | Multi-campus support within a single institution: campus-level data and reporting | Must Have |
| CRM-SA-003 | Academic year / admission cycle management with rollover | Must Have |
| CRM-SA-004 | Full audit trail for all CRM data changes (who changed what, when) | Must Have |
| CRM-SA-005 | Data import and export tools (leads, applications, contacts) in CSV/Excel | Must Have |
| CRM-SA-006 | System configuration: business hours, time zones, language/locale, institution branding | Must Have |
| CRM-SA-007 | Workflow and automation template library (pre-built journeys for common scenarios) | Should Have |
| CRM-SA-008 | Custom field management for leads, applications, and students | Must Have |
| CRM-SA-009 | Email and notification template management | Must Have |
| CRM-SA-010 | Integration credential management (API keys, gateway configurations) with encrypted storage | Must Have |
| CRM-SA-011 | System health monitoring dashboard for admins (API status, queue depths, error logs) | Should Have |
| CRM-SA-012 | Backup and restore capabilities with configurable frequency | Must Have |

---

## 9. Non-Functional Requirements

### 9.1 Performance

| Req ID | Requirement | Target |
|---|---|---|
| NFR-P-001 | Page load time (dashboard, lead list, lead record) | < 3 seconds (standard connection) |
| NFR-P-002 | Search results (lead search, report query) | < 2 seconds |
| NFR-P-003 | Concurrent user support per institution | Minimum 500 concurrent users |
| NFR-P-004 | Bulk operation (email blast to 10,000 leads) | Initiated within 30 seconds, completed per gateway SLA |
| NFR-P-005 | API response time for all integration endpoints | < 500ms (95th percentile) |

### 9.2 Scalability

| Req ID | Requirement |
|---|---|
| NFR-SC-001 | System architecture shall support horizontal scaling to accommodate growth to 1 million+ lead records per institution |
| NFR-SC-002 | Database design shall support partitioning by institution and academic year |
| NFR-SC-003 | Microservices architecture shall allow independent scaling of communication engine, AI layer and core CRM |

### 9.3 Availability and Reliability

| Req ID | Requirement |
|---|---|
| NFR-AV-001 | System uptime SLA: 99.5% (excluding planned maintenance) |
| NFR-AV-002 | Planned maintenance windows shall be scheduled during off-peak hours with advance notification |
| NFR-AV-003 | Automated failover for database and application servers |
| NFR-AV-004 | RTO (Recovery Time Objective): < 4 hours; RPO (Recovery Point Objective): < 1 hour |

### 9.4 Security

| Req ID | Requirement |
|---|---|
| NFR-SE-001 | All data in transit shall be encrypted using TLS 1.2 or higher |
| NFR-SE-002 | All data at rest (documents, PII) shall be encrypted using AES-256 |
| NFR-SE-003 | Multi-factor authentication (OTP-based) shall be available for all user roles |
| NFR-SE-004 | Role-based access control (RBAC) with field-level data visibility control |
| NFR-SE-005 | IP whitelisting and session management controls |
| NFR-SE-006 | Penetration testing shall be conducted prior to go-live and annually thereafter |
| NFR-SE-007 | OWASP Top 10 compliance shall be verified in all releases |

### 9.5 Usability

| Req ID | Requirement |
|---|---|
| NFR-UX-001 | UI shall be intuitive enough for a counsellor with basic computer literacy to use core functions within 2 hours of onboarding |
| NFR-UX-002 | All key workflows shall be completable within 3 clicks/screens |
| NFR-UX-003 | System shall be fully responsive for use on tablets and mobile browsers |
| NFR-UX-004 | Support for Hindi UI localisation as a future option (English as base language for v1.0) |

### 9.6 Maintainability

| Req ID | Requirement |
|---|---|
| NFR-MT-001 | Codebase shall follow MEETCS coding standards (Laravel / Vue.js) for consistency with A2A ERP |
| NFR-MT-002 | All integration points shall use versioned REST APIs |
| NFR-MT-003 | Comprehensive API documentation shall be maintained |
| NFR-MT-004 | Unit test coverage ≥ 70% for core modules |

---

## 10. Data Requirements

### 10.1 Core Data Entities

| Entity | Key Attributes |
|---|---|
| Lead / Enquiry | ID, name, mobile, email, source, score, status, assigned counsellor, programme(s) of interest, academic background, tags, custom fields, creation timestamp, last activity timestamp |
| Application | ID, lead ID, programme, batch, form data, submission date, status, application fee paid, offer letter reference |
| Communication Log | ID, lead ID, channel, direction (in/out), content/reference, timestamp, delivery status, counsellor |
| Task | ID, lead ID, type, due date, assigned to, status, disposition, notes |
| Document | ID, lead/application ID, document type, upload timestamp, verification status, storage path |
| Payment | ID, lead/application ID, amount, gateway, transaction ID, status, timestamp, type (application fee / booking amount) |
| Campaign | ID, name, channel, target segment, content, schedule, performance metrics |
| Counsellor / User | ID, name, role, institution, campus, active leads, performance metrics |
| Agent / Partner | ID, name, contact, referral code, commission structure, performance history |

### 10.2 Data Retention

- Lead records shall be retained for a minimum of 7 years from last activity, in accordance with educational institution regulatory requirements.
- Deleted/withdrawn applicant data shall be anonymised (not hard-deleted) to preserve aggregate analytics.
- DPDP Act right-to-erasure requests shall anonymise PII while retaining anonymised aggregate data.

### 10.3 Data Migration

- Import tooling shall support migration from CSV/Excel exports of common systems (Meritto, LeadSquared, ExtraAedge, Mastersoft, custom Excel-based systems).
- A data mapping wizard shall facilitate field-to-field mapping during migration.
- A migration validation report shall be generated post-import with error counts and sample records.

---

## 11. Compliance and Regulatory Requirements

| Req ID | Requirement | Regulation |
|---|---|---|
| CRM-CR-001 | Explicit consent shall be captured at the point of lead creation for use of personal data for marketing communication | DPDP Act 2023 |
| CRM-CR-002 | Consent records shall be stored with timestamp, IP address, and form version | DPDP Act 2023 |
| CRM-CR-003 | Opt-out/unsubscribe requests shall be honoured within 24 hours and logged | DPDP Act 2023 |
| CRM-CR-004 | Right-to-access: applicants shall be able to request a copy of their stored data from the student portal | DPDP Act 2023 |
| CRM-CR-005 | Right-to-erasure: verified erasure requests shall anonymise PII within 30 days | DPDP Act 2023 |
| CRM-CR-006 | All personal data of Indian residents shall be stored on servers physically located in India | DPDP Act 2023 |
| CRM-CR-007 | Call recordings shall require explicit consent notification to the caller | TRAI Regulations |
| CRM-CR-008 | SMS communications shall use DLT-registered templates and sender IDs | TRAI / DoT |
| CRM-CR-009 | Data Processing Agreements shall be available for institutions as Data Fiduciaries | DPDP Act 2023 |
| CRM-CR-010 | Breach notification workflow shall alert institution admin and provide incident documentation within 72 hours of a detected breach | DPDP Act 2023 |

---

## 12. User Roles and Permissions

| Role | Scope | Key Permissions |
|---|---|---|
| Super Admin (MEETCS) | All institutions | Full system access, platform configuration, support tools |
| Institution Admin | Single institution | User management, system configuration, all data access, all reports |
| Admissions Director / Head | Institution or Campus | All CRM data for their scope, management dashboards, user oversight |
| Admissions Manager | Team | Team lead management, reassignment, team dashboards, approval workflows |
| Counsellor (Senior) | Assigned leads | Own lead management, communication, task management, limited reports |
| Counsellor (Junior) | Assigned leads | Own lead management, guided workflows, no fee approvals |
| Marketing Manager | Institution | Campaign management, lead source management, marketing analytics |
| Finance Officer | Institution | Fee management, payment reports, scholarship approvals |
| Document Verifier | Institution | Document review and verification only |
| Agent / Partner | Own leads | Lead submission, lead status view (own leads), commission view |
| Applicant (Student) | Own record | Student portal — own application, documents, payments, communication |

---

## 13. Feature Priority Matrix

Features are prioritised using MoSCoW methodology.

| Priority | Definition | Delivery Target |
|---|---|---|
| **Must Have** | Non-negotiable for MVP go-live; core value proposition | Phase 1 (Months 1–4) |
| **Should Have** | High value; important but deliverable in Phase 2 | Phase 2 (Months 5–8) |
| **Could Have** | Desirable; included if capacity allows | Phase 3 (Months 9–12) |
| **Won't Have (this release)** | Explicitly deferred to future roadmap | Post v1.0 |

### Summary Count by Priority

| Module | Must Have | Should Have | Could Have |
|---|---|---|---|
| Lead Capture | 13 | 4 | 0 |
| Lead Scoring | 7 | 3 | 0 |
| Enquiry & Counselling | 14 | 5 | 0 |
| Application & Pipeline | 14 | 1 | 0 |
| Communication Engine | 19 | 4 | 0 |
| Marketing Automation | 7 | 3 | 0 |
| Fee & Payments | 9 | 3 | 0 |
| Document Management | 8 | 2 | 0 |
| Tasks & Follow-ups | 9 | 0 | 0 |
| Telecalling | 5 | 4 | 0 |
| Student Portal | 6 | 1 | 0 |
| Agent Management | 5 | 2 | 0 |
| Alumni Bridge | 1 | 2 | 1 |
| Analytics & Reporting | 15 | 5 | 1 |
| AI / Agentic Layer | 5 | 6 | 1 |
| Mobile Application | 6 | 2 | 0 |
| ERP Integration | 7 | 2 | 1 |
| Third-Party Integrations | 10 | 3 | 1 |
| System Administration | 9 | 3 | 0 |
| **TOTAL** | **179** | **55** | **5** |

---

## 14. Glossary

| Term | Definition |
|---|---|
| A2A ERP | Admissions-2-Alumni Educational ERP — MEETCS Pvt. Ltd.'s flagship product |
| A2A-CRM | The Educational CRM module being specified in this document |
| BSP | Business Solution Provider — authorised WhatsApp API partner |
| DLT | Distributed Ledger Technology — TRAI's SMS regulatory platform |
| DPDP Act | Digital Personal Data Protection Act, 2023 (India) |
| ERP | Enterprise Resource Planning |
| IVR | Interactive Voice Response |
| KPI | Key Performance Indicator |
| Lead | A prospective student who has expressed interest in a programme |
| MoSCoW | Prioritisation framework: Must Have / Should Have / Could Have / Won't Have |
| NPS | Net Promoter Score |
| OTP | One-Time Password |
| PII | Personally Identifiable Information |
| RBAC | Role-Based Access Control |
| RPO | Recovery Point Objective |
| RTO | Recovery Time Objective |
| UTM | Urchin Tracking Module — URL parameter standard for campaign tracking |
| WhatsApp BSP | WhatsApp Business Solution Provider |

---

## 15. Appendices

### Appendix A — Suggested Phased Delivery Roadmap

| Phase | Duration | Key Deliverables |
|---|---|---|
| Phase 1 — Foundation | Months 1–4 | Lead capture, lead management, counsellor workflows, basic communication (Email/SMS/WhatsApp), application forms, basic analytics, ERP integration (Programme Master, Fee, Student conversion), student portal, admin configuration |
| Phase 2 — Engagement | Months 5–8 | Marketing automation, AI lead scoring, next best action, telecalling module, document management with API Setu, agent portal, advanced analytics and dashboards, mobile app |
| Phase 3 — Intelligence | Months 9–12 | Agentic AI chatbot, predictive enrolment forecasting, gamification for counsellors, alumni bridge, Power BI / Tableau API, call transcription, advanced automation templates |

### Appendix B — Integration Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        A2A-CRM                              │
│                                                             │
│  Lead Capture  →  Scoring  →  Counselling  →  Application  │
│       ↓               ↓            ↓               ↓       │
│  Communication    AI Layer     Fee Engine     Doc Manager   │
│       ↓               ↓            ↓               ↓       │
│  ──────────────── Analytics & Reporting ─────────────────  │
│                                                             │
│  ════════════ A2A ERP Integration Layer ════════════════   │
│        ↓              ↓            ↓            ↓          │
│  A2A Student    A2A Fee Module  CamPLUS LMS  A2A Alumni    │
│    Master                        / Moodle     Module       │
└─────────────────────────────────────────────────────────────┘
         ↕                ↕               ↕
  External CRMs     Payment GWs     Social/Ad Platforms
  (Migration In)    (Razorpay etc)  (Meta, Google)
         ↕                ↕               ↕
  Telephony/IVR     API Setu/DL     WhatsApp BSP
  (Exotel etc)      (DigiLocker)    (Interakt etc)
```

### Appendix C — References

- Meritto (NoPaperForms) Product Documentation — meritto.com
- LeadSquared Education CRM Documentation — leadsquared.com/education
- ExtraAedge Platform Overview — extraaedge.com
- Digital Personal Data Protection Act, 2023 — India
- TRAI Commercial Communications Regulations
- API Setu Documentation — apisetu.gov.in
- MEETCS Internal: DPDP Act Compliance Work — Goa Institute of Management (2025)
- MEETCS Internal: A2A Agentic AI CRM Module SRS (2025)
- MEETCS Internal: A2A ERP Data Model v3.x

---

*End of Document*

**Document Classification:** Internal / Confidential — MEETCS Pvt. Ltd.
**Next Review Date:** 30 days from issue date
**Approval Required From:** Dr. Mahendra Gupta (Co-Founder & MD), A2A Product Lead, Development Lead
