# A2A CRM - AP-004 User Manual
## Application Fee at Submission (Sprint 3 Group M)
**Version:** 1.0
**Date:** April 2026
**BRD Req ID:** CRM-AP-004

---

## 1. Overview
AP-004 enables configurable application fee payment at submission stage for application drafts.

When AP-004 is enabled on a template:
- Draft submission is blocked until fee status is paid.
- Fee amount and currency are shown on web/public fill screens.
- Fee can be recorded through API, CRM web, and public resume flow.

---

## 2. Configuration
Configure AP-004 in template settings:
- settings.application_fee_enabled = true
- settings.application_fee_amount = positive numeric value
- settings.application_fee_currency = 3-letter ISO currency (for example INR)

Validation rules:
- If fee is enabled, fee amount must be greater than zero.
- Currency must be uppercase 3-letter code.

---

## 3. Access Paths
### CRM web app
- Template settings: /crm/applications/forms/create
- Draft resume and fee payment: /crm/applications/drafts/{uuid}/resume

### Public applicant web
- Public resume and fee payment: /apply/resume/{resumeToken}

### Integration API
- Pay draft fee: POST /api/v1/crm/application-form-drafts/{uuid}/fee/pay
- Submit draft: POST /api/v1/crm/application-form-drafts/{uuid}/submit

---

## 4. Staff Workflow (CRM Web)
1. Enable AP-004 fee settings on template.
2. Create or resume draft.
3. Click Pay Fee Now on draft screen.
4. Confirm fee status becomes paid.
5. Submit draft.

Expected behavior:
- Submit fails while fee status is pending.
- Submit succeeds after fee status is paid and completeness threshold is satisfied.

---

## 5. Public Applicant Workflow
1. Open public form and save draft.
2. Resume using /apply/resume/{resumeToken}.
3. Click Pay Fee Now.
4. Submit application.

Expected behavior:
- Public submit is blocked if fee not paid.
- Public submit succeeds after fee payment and completeness threshold check.

---

## 6. API Workflow
1. Create draft for AP-004-enabled template.
2. Attempt submit (will fail with application_fee_status validation error while unpaid).
3. Call fee payment endpoint.
4. Submit draft again.

Example fee payment payload:

```json
{
  "gateway": "online",
  "transaction_reference": "APFEE-TEST-1001"
}
```

---

## 7. Troubleshooting
### Submission blocked with application_fee_status error
- Fee is enabled and fee status is still pending.
- Pay fee first, then submit again.

### Fee setup not taking effect
- Verify template has application_fee_enabled true.
- Verify application_fee_amount is positive.

### Currency validation error
- Use uppercase 3-letter code (example INR).

---

## 8. Audit Notes
- Fee status and payment metadata are stored on draft record.
- Payment reference and gateway value are captured for traceability.
- Multi-tenant boundaries remain enforced on all fee actions.
