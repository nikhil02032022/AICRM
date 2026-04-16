# A2A CRM - AP-003 User Manual
## Application Form Save-and-Resume Configuration (Sprint 3 Group M)
**Version:** 1.0
**Date:** April 2026
**BRD Req ID:** CRM-AP-003

---

## 1. Overview
AP-003 enables save-and-resume capability at the application form template level using the template setting:

- settings.allow_save_and_resume

When enabled for a template, the system now supports draft journeys across:

- CRM web app staff workflows (session-authenticated).
- Public applicant form workflows (no-login resume token links).
- Integration API consumers (mobile app, ERP, approved third-party).

---

## 2. Who Can Use It
| Permission | Capability |
|---|---|
| crm.applications.view | View application form templates and settings payload |
| crm.applications.create | Create resume-enabled templates |
| crm.applications.edit | Update existing templates to enable or disable save-and-resume |

---

## 3. Access Paths
### CRM web app
- Application Forms list: /crm/applications/forms
- Create template: /crm/applications/forms/create
- Edit template: /crm/applications/forms/{uuid}/edit
- Fill template (staff): /crm/applications/forms/{uuid}/fill
- Resume staff draft: /crm/applications/drafts/{uuid}/resume

### Public applicant web
- Fill public form: /apply/{slug}
- Resume public draft: /apply/resume/{resumeToken}

### Integration API (external consumers only)
- Create template: POST /api/v1/crm/application-form-templates
- Update template: PUT /api/v1/crm/application-form-templates/{uuid}
- View template: GET /api/v1/crm/application-form-templates/{uuid}
- Create draft: POST /api/v1/crm/application-form-templates/{uuid}/drafts
- Save draft: PUT /api/v1/crm/application-form-drafts/{uuid}
- Submit draft: POST /api/v1/crm/application-form-drafts/{uuid}/submit
- Get draft by UUID: GET /api/v1/crm/application-form-drafts/{uuid}
- Resume by token: GET /api/v1/crm/application-form-drafts/resume/{resumeToken}

Note:
- API routes are for React Native, A2A ERP, and approved third-party integrations.
- CRM web pages use web routes and session authentication only.

---

## 4. Enable AP-003 During Template Create (Web or API)
### CRM web app
1. Open Application Forms create or edit screen.
2. In AP-003 Save and Resume Settings, enable Allow save and resume.
3. Save template.

### API
1. Authenticate with Sanctum token having application permissions.
2. Send template payload including:
   - settings.allow_save_and_resume = true
3. Submit the create request.
4. Verify in response data.settings.allow_save_and_resume that the value is true.

Example settings block:

```json
{
  "settings": {
    "allow_save_and_resume": true,
    "mobile_optimised": true,
    "show_progress_bar": true
  }
}
```

---

## 5. Enable or Disable AP-003 on Existing Template (API)
1. Get template UUID from list or show API.
2. Call update endpoint with settings payload.
3. Set allow_save_and_resume to:
   - true to enable resume behavior
   - false to disable resume behavior
4. Confirm updated value in API response.

---

## 6. CRM Web Staff Workflow (Save, Resume, Submit)
1. Open Application Forms list and click Fill Form for a template.
2. Enter applicant draft data and click Save Draft.
3. System creates a draft and redirects to staff draft resume URL.
4. Continue editing and save again as needed.
5. Click Submit when form completeness is sufficient.
6. System validates completeness threshold and marks draft as submitted.

Notes:
- Staff workflow uses web routes and session auth only.
- Draft save and submit are tenant-scoped and policy-protected.

---

## 7. Public Applicant Workflow (Save, Resume, Submit)
1. Open public application URL: /apply/{slug}.
2. Fill available fields and click Save Draft.
3. System returns/redirects to resume URL with token: /apply/resume/{resumeToken}.
4. Re-open resume URL later to continue filling.
5. Click Submit when complete.
6. System validates completeness threshold and updates status to submitted.

Notes:
- Public workflow still enforces institution scoping through template slug mapping.
- Resume links are token-based and fail for expired drafts.

---

## 8. Validation and System Behavior
1. settings.allow_save_and_resume accepts boolean values only.
2. Invalid types (for example string) fail validation.
3. Template remains institution-scoped under multi-tenant controls.
4. AP-002 mandatory section validation still applies when sections are updated.
5. Draft create fails if selected template has allow_save_and_resume disabled.
6. Draft responses include a resume_token for resume flow.
7. Draft submit fails when progress_percentage is below template minimum_completeness_percentage.
8. After successful submit, draft status is set to submitted and submitted_at is recorded.

---

## 9. Draft Workflow (API)
1. Start draft using template UUID where save-and-resume is enabled.
2. Capture returned draft UUID and resume_token.
3. Save partial form data using PUT draft endpoint.
4. Resume later using resume token endpoint.
5. Submit draft using draft submit endpoint after required completeness is reached.
6. Continue with downstream AP pipeline steps (AP-008 onward) after submission status is confirmed.

---

## 10. Troubleshooting
### Save-and-resume value not applied
- Confirm payload is nested under settings.allow_save_and_resume.
- Confirm request uses boolean true/false, not quoted strings.

### 403 or permission denied
- Verify user/token has crm.applications.create or crm.applications.edit.

### 404 on template UUID
- Verify UUID belongs to the same institution tenant.

### 422 validation error
- Check JSON structure and data types in settings.

### Draft create blocked for AP-003
- Verify template settings.allow_save_and_resume is true.

### Resume token does not work
- Verify token belongs to same institution tenant.
- Verify draft is not expired.

### CRM staff cannot submit draft
- Verify draft status is still draft and not already submitted.
- Verify progress meets minimum_completeness_percentage configured on template.

### Public /apply URL not loading
- Verify template slug exists and belongs to an active institution template.
- Verify AP-003 is enabled on that template.

---

## 11. Audit and Compliance Notes
1. Template updates are institution-scoped and audited.
2. No applicant PII is required to configure the AP-003 flag.
3. Draft form payload is encrypted at rest.
4. Follow DPDP controls in downstream submission and communication flows that process personal data.
