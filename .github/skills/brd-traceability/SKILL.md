---
name: brd-traceability
description: "Map existing code back to BRD requirements, check BRD coverage gaps, or validate a module against MEETCS-BRD-CRM-001. Use when: validating BRD coverage, traceability audit, checking which BRD Req IDs are missing from an implementation, generating a traceability matrix, or verifying MoSCoW priority alignment. Trigger: BRD coverage, traceability, requirements gap, CRM-LC, CRM-LQ, CRM-EC, CRM-AP."
argument-hint: "Module name, file path, or BRD section to trace (e.g. 'LeadService' or 'section 8.1 Lead Capture')"
---

# BRD Traceability

Maps implemented code to MEETCS-BRD-CRM-001 v1.0 requirements and identifies coverage gaps.

## When to Use

- Auditing a service/module for BRD completeness before phase delivery
- Generating a traceability matrix for stakeholder review
- Finding which BRD Req IDs still need implementation
- Validating that no out-of-scope features were added

## Complete BRD Requirement Index

### Phase 1 — Must Have (Months 1–4)

| Module | Req ID Range | Key Must-Haves |
|--------|-------------|----------------|
| Lead Capture (8.1) | CRM-LC-001–020 | Web forms, Google/FB import, QR, CSV, dedup, UTM |
| Lead Scoring (8.2) | CRM-LQ-001–010 | Rule engine 0–100, HOT/WARM/COLD, thresholds |
| Enquiry & Counselling (8.3) | CRM-EC-001–019 | Lead record, assignment, status pipeline, appointments |
| Application & Pipeline (8.4) | CRM-AP-001–019 | Form builder, Kanban, offer letter, ERP handoff |
| Communication Engine (8.5) | CRM-CC-001–023 | Email, SMS, WhatsApp, Voice, unified inbox |
| Marketing Automation (8.6) | CRM-MA-001–010 | Workflow builder, drip campaigns, triggers |
| Fee & Payments (8.7) | CRM-FM-001–013 | Application fee, booking, gateways, scholarships |
| Document Management (8.8) | CRM-DM-001–010 | Checklists, upload, verification, reminders |
| Tasks & Follow-ups (8.9) | CRM-TF-001–009 | Task creation, auto-follow-up, calendar, escalation |
| Telecalling (8.10) | CRM-TC-001–009 | Dispositions, calling campaigns, DNC, recording |
| Student Portal (8.11) | CRM-SP-001–008 | Branded portal, OTP auth, status, docs, payments |
| Agent Management (8.12) | CRM-AG-001–008 | Agent profiles, referral codes, commissions |
| Analytics (8.14) | CRM-AR-001–021 | Dashboards, 9 standard reports, export |
| AI Layer (8.15) | CRM-AI-001–012 | Lead scoring, NBA, drafting, priority list |
| Mobile App (8.16) | CRM-MB-001–008 | iOS/Android, push notifications, click-to-call |
| ERP Integration (8.17) | CRM-EI-001–010 | Programme sync, fee sync, Student Master conversion |
| System Admin (8.19) | CRM-SA-001–012 | Multi-tenant, audit trail, integrations, backup |
| DPDP Compliance | CRM-CR-001–010 | Consent, opt-out, erasure, DLT, breach notification |

### Phase 2 — Should Have (Months 5–8)

AI-assisted scoring, telecalling power dialler, DigiLocker integration, A/B testing, custom report builder, alumni bridge, advanced automation, Zoom/Meet video counselling.

### Phase 3 — Could Have (Months 9–12)

Agentic chatbot, call transcription, predictive forecasting, gamification, Power BI API.

## Procedure

### Step 1 — Read the Code

Use search tools to find all service methods, controllers, and models in the specified module.

### Step 2 — Match to BRD

For each method/feature found:
1. Identify which BRD Req ID it satisfies
2. Check if BRD comment annotation is present: `// BRD: CRM-XX-NNN — description`
3. If annotation missing, add it

### Step 3 — Find Gaps

Compare implemented methods against the full Req ID list for the module.
Flag any Must Have requirements not yet implemented.

### Step 4 — Check for Scope Creep

Identify any implemented features NOT covered by a BRD Req ID. Flag for product review.

### Step 5 — Output Traceability Matrix

```markdown
# BRD Traceability Matrix — {Module}
**BRD Version:** MEETCS-BRD-CRM-001 v1.0
**Date:** {date}

## Coverage Summary
- Total Requirements: XX
- ✅ Implemented: XX
- ❌ Not Implemented (Must Have): XX
- ⚠️ Partial / Needs Review: XX
- 🔵 Deferred (Should/Could Have): XX

## Requirement → Code Map
| Req ID | Requirement | Status | Implementation | Notes |
|--------|-------------|--------|----------------|-------|
| CRM-LC-001 | Embeddable web enquiry form | ✅ | LeadController::embedForm() | |
| CRM-LC-018 | Duplicate detection | ✅ | DuplicateDetectionService::check() | BRD comment present |
| CRM-LC-019 | Manual lead merge | ❌ | NOT IMPLEMENTED | Priority: Must Have |

## Out-of-Scope Items Found
[List any code not traceable to a BRD requirement]

## DPDP Requirement Coverage
[CRM-CR-001 through CRM-CR-010 status]
```
