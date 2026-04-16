# A2A CRM - AP-007 User Manual
## Mandatory vs Optional Fields and Completeness Threshold (Sprint 3 Group M)
**Version:** 1.0
**Date:** April 2026
**BRD Req ID:** CRM-AP-007

---

## 1. Overview
AP-007 allows institutions to define:
- Mandatory fields (`required = true`)
- Optional fields (`required = false`)
- Minimum completeness threshold (`minimum_completeness_percentage`)

This gives admissions teams control over submission readiness without hardcoding field behavior.

---

## 2. Configuration Points
AP-007 is configured in application form template builder:
- Field-level `required` toggle in each section field
- Template-level `minimum_completeness_percentage` value

Paths:
- CRM template create/edit: /crm/applications/forms/create
- CRM template edit: /crm/applications/forms/{uuid}/edit

---

## 3. Runtime Enforcement
At draft submission:
1. Minimum completeness threshold is validated.
2. Mandatory fields in submitted sections are validated.
3. Optional fields can remain blank.

If a mandatory field is blank in submitted form_data section, submission is rejected with field-specific validation errors.

---

## 4. Staff Workflow
1. Open template builder.
2. Set required toggle for fields that must be mandatory.
3. Set minimum completeness percentage.
4. Save template.
5. Use fill/resume flow and submit drafts.

Expected behavior:
- Mandatory field omissions fail submit.
- Optional field omissions do not fail submit directly.
- Threshold must still be satisfied.

---

## 5. API Behavior
Draft submit endpoint:
- POST /api/v1/crm/application-form-drafts/{uuid}/submit

Possible AP-007 failure shape:
- Validation error on field path such as `form_data.digital_signature.applicant_signature`

---

## 6. Troubleshooting
### Submission fails on mandatory field
- Check the specific field path returned in validation errors.
- Ensure required field has non-empty value in submitted section data.

### Submission fails on progress threshold
- Verify draft progress meets template minimum_completeness_percentage.
- Save additional section data and retry.

---

## 7. Compliance Notes
- AP-007 configuration is institution-scoped per template.
- Validation behavior is enforced in service layer for submission.
- Multi-tenant boundaries remain unchanged.
