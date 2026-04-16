# A2A CRM - AP-006 User Manual
## Mobile-Optimised Application Form Flow (Sprint 3 Group M)
**Version:** 1.0
**Date:** April 2026
**BRD Req ID:** CRM-AP-006

---

## 1. Overview
AP-006 enforces mobile-optimised behavior across application form configuration and execution.

When AP-006 is active:
- Templates must keep settings.mobile_optimised enabled.
- Draft operations are blocked for templates that are not mobile-optimised.
- Public apply links for non-mobile templates are not exposed.

---

## 2. Configuration Rules
AP-006 uses template setting:
- settings.mobile_optimised = true

Validation behavior:
- Template create/update requests fail when mobile_optimised is false.
- Existing non-mobile templates cannot be used for draft create/save/submit/pay workflows.

---

## 3. Access Paths
### CRM web app
- Template builder: /crm/applications/forms/create
- Staff fill route: /crm/applications/forms/{uuid}/fill
- Staff draft resume: /crm/applications/drafts/{uuid}/resume

### Public applicant web
- Public apply entry: /apply/{slug}
- Public resume entry: /apply/resume/{resumeToken}

### Integration API
- Create draft: POST /api/v1/crm/application-form-templates/{uuid}/drafts
- Save draft: PUT /api/v1/crm/application-form-drafts/{uuid}
- Submit draft: POST /api/v1/crm/application-form-drafts/{uuid}/submit

---

## 4. CRM Staff Workflow
1. Open template create/edit screen.
2. Ensure mobile optimisation remains enabled.
3. Open Fill Form from application form list.
4. Continue save/resume/submit flow.

Expected behavior:
- If template is not mobile-optimised, Fill Form route redirects to forms list with error.
- Draft resume is blocked for non-mobile templates.

---

## 5. Public Applicant Workflow
1. Applicant opens /apply/{slug} for active template.
2. Applicant saves, resumes, and submits draft.

Expected behavior:
- Public route returns not found for templates where mobile_optimised is disabled.
- Only AP-006-compliant templates are exposed to applicants.

---

## 6. API Workflow
1. Create a draft from a mobile-optimised template.
2. Save progress and submit when completeness threshold is met.

Expected behavior:
- API returns validation error settings.mobile_optimised when template is not mobile-optimised.

---

## 7. Troubleshooting
### Error on settings.mobile_optimised
- Template is not AP-006 compliant.
- Re-enable mobile optimisation in template settings and retry.

### Staff fill action redirects back to forms list
- Template mobile optimisation is disabled.
- Edit template and set settings.mobile_optimised to true.

### Public apply link shows not found
- Template is inactive or AP-006 mobile optimisation is disabled.
- Verify template status and AP-006 setting.

---

## 8. Compliance and Audit Notes
- AP-006 checks run in request validation and service layer runtime guards.
- Public and CRM entry points consistently block non-mobile templates.
- Institution scoping and access controls remain unchanged.
