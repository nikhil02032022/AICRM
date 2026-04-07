---
description: "Run a full DPDP Act 2023 compliance audit on selected code, a service, or an entire CRM module. Checks consent capture, PII in logs, encryption, opt-out handling, erasure capability, India data residency, call recording consent, DLT SMS compliance, and AI audit logging."
agent: "agent"
tools: [read, search]
argument-hint: "File path, service name, or module to audit (e.g. 'app/Services/CRM/LeadService.php' or 'Lead Capture module')"
---

You are performing a DPDP Act 2023 compliance audit on A2A-CRM code. Read the specified files/module and evaluate against every requirement below.

## Audit Checklist

For each item, report: ✅ PASS | ❌ FAIL (with file:line reference) | ⚠️ PARTIAL | N/A

### Consent (CRM-CR-001, CRM-CR-002)
- [ ] `consent_given` boolean recorded at every lead/applicant creation path
- [ ] `consent_timestamp`, `consent_ip`, `consent_form_version` captured alongside consent
- [ ] Bulk CSV imports validated for consent metadata
- [ ] API endpoint validates `consent_given: required|accepted` in FormRequest

### PII in Logs (CRM-CR-002)
- [ ] No `Log::*()` call contains: name, mobile, email, aadhaar, dob, address
- [ ] Queue job logs reference UUID/ID only, never PII fields

### Encryption at Rest (NFR-SE-002)
- [ ] `mobile`, `email`, `aadhaar_number`, `dob` columns use `'encrypted'` cast
- [ ] Documents stored on S3 with server-side encryption
- [ ] No PII cached in Redis without encryption and TTL

### Data Residency (CRM-CR-006)
- [ ] S3 region configured as `ap-south-1`
- [ ] No cross-region replication configured
- [ ] Redis on same VPC in ap-south-1

### Opt-Out / DNC (CRM-CR-003)
- [ ] `opt_out` check present before EVERY communication dispatch
- [ ] Opt-out job is idempotent
- [ ] DNC list checked before any outbound call (CRM-TC-009)
- [ ] Opt-out removal from automation sequences (CRM-MA-006)

### Right to Erasure (CRM-CR-005)
- [ ] `AnonymisePIIJob` available and covers all PII fields
- [ ] Pre-anonymisation: documents deleted from S3
- [ ] Post-anonymisation: aggregate analytics preserved
- [ ] Hard DELETE statements absent from all erasure code paths

### Call Recording Consent (CRM-CR-007)
- [ ] `call_consent_given` guard present before any recording start
- [ ] Exception thrown (not silently skipped) when consent absent

### SMS DLT Compliance (CRM-CR-008)
- [ ] `dlt_template_id` present on SmsTemplate model
- [ ] `dlt_sender_id` present and validated
- [ ] No dynamic content injected outside approved template variables

### AI Audit Trail (CRM-AI-012)
- [ ] Every `AnthropicService` call logs to `ai_usage_logs`
- [ ] No raw PII in AI prompt payloads
- [ ] AI output confirmed as suggestion before any action

### Access Control
- [ ] `Gate::authorize()` present on every controller action
- [ ] Institution scope active on all model queries
- [ ] No `withoutGlobalScope(InstitutionScope::class)` in CRM code paths

## Audit Report Format

```
# DPDP Compliance Audit Report
**Module/File:** {name}
**Date:** {date}
**Auditor:** GitHub Copilot (@brd-analyst)

## Summary
- Total checks: XX
- ✅ Pass: XX
- ❌ Fail: XX  
- ⚠️ Partial: XX

## Critical Failures (Must Fix Before Production)
### ❌ {Issue Title}
- **File:** path/to/file.php:line
- **Problem:** Description
- **Fix:** Specific code change required
- **BRD Req:** CRM-CR-XXX

## Recommendations
...
```
