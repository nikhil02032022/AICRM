# A2A CRM - AP-002 User Manual
## Application Form Sections and Signature Support (Sprint 3 Group M)
**Version:** 1.0
**Date:** April 2026
**BRD Req ID:** CRM-AP-002

---

## 1. Overview
AP-002 ensures every application form template supports the mandatory section families:

1. personal_details
2. academic_history
3. entrance_exam_scores
4. co_curricular_activities
5. declarations
6. digital_signature

A template is AP-002-ready only when all section families are present and at least one field has type signature.

---

## 2. Who Can Use It
| Permission | Capability |
|---|---|
| crm.applications.view | View template list and AP-002 coverage |
| crm.applications.create | Create AP-002-compliant template |
| crm.applications.edit | Edit and revalidate AP-002 compliance |

---

## 3. Navigation
1. Login to CRM web app.
2. Open left navigation.
3. Go to Application Forms.
4. Click New Template or Edit on an existing template.

Route family:
- /crm/applications/forms

---

## 4. AP-002 Readiness Preview
On the create/edit page, review the AP-002 Readiness Preview card before saving:

1. Required section IDs show Present or Missing.
2. Signature field status shows found or missing.
3. Duplicate section and field ID checks are shown.
4. The status chip shows Ready for AP-002 when all checks pass.

---

## 5. Create a Compliant Template
1. Fill Template Name and optional Description.
2. Keep or adjust preloaded AP-002 section blocks.
3. Ensure declarations section includes required policy confirmations.
4. Ensure digital_signature section contains at least one signature type field.
5. Click Create Template.

System behavior:
- Save fails if any mandatory AP-002 section is missing.
- Save fails if no signature type field exists.
- Save fails for duplicate section IDs or duplicate field IDs within one section.

---

## 6. Validation Error Reference
| Error | Meaning | Fix |
|---|---|---|
| Missing AP-002 sections | One or more mandatory section families are absent | Add required section IDs |
| Signature field missing | No field with type signature exists | Add signature field in digital_signature section |
| Section IDs must be unique | Duplicate section IDs found | Rename duplicate section IDs |
| Field IDs must be unique within each section | Duplicate field IDs in same section | Rename duplicate field IDs |

---

## 7. Audit and Multi-Tenant Behavior
1. Templates are institution-scoped.
2. Cross-institution users cannot access another institution template.
3. API and web both enforce the same AP-002 validation constraints.

---

## 8. Troubleshooting
### Save fails with AP-002 validation
- Check AP-002 readiness preview first.
- Confirm all required section IDs exactly match the expected values.

### Signature field not detected
- Ensure field type is signature, not text or file.

### Update works for one user but not another
- Verify user belongs to same institution and has required permissions.
