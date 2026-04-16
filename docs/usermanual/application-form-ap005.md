# A2A CRM - AP-005 User Manual
## Simultaneous Applications to Multiple Programmes (Sprint 3 Group M)
**Version:** 1.0
**Date:** April 2026
**BRD Req ID:** CRM-AP-005

---

## 1. Overview
AP-005 enables applicants to select multiple programmes within the same institution in a single application draft/submission journey.

This feature works across:
- CRM web staff draft flow
- Public applicant draft flow
- Integration API draft flow

---

## 2. Template Configuration
Enable AP-005 in application template settings:
- settings.allow_multi_programme_applications = true
- settings.max_programmes_per_application = 2 to 10

Validation:
- If multi-programme is enabled, max_programmes_per_application must be at least 2.
- Selected programme list is validated against active institution programme catalogue.

---

## 3. User Flows
### CRM web app
1. Open template builder and enable AP-005 settings.
2. Open draft fill/resume page.
3. Select multiple programmes from programme checklist.
4. Save and submit draft.

### Public applicant flow
1. Open public form and save draft.
2. Resume using token URL.
3. Select one or more programmes (within configured max).
4. Submit draft.

### API flow
1. Create/update/submit draft with programme_uuids array.
2. Ensure all values are valid active programme UUIDs for the same institution.

---

## 4. API Contract (AP-005)
Draft endpoints now accept:
- programme_uuids: string[] (UUID format), max 10 in payload-level validation

Template-level max is enforced by service rules.

Example:

```json
{
  "progress_percentage": 85,
  "programme_uuids": [
    "2f3d66db-8f4e-4a96-9c72-64b973a8f001",
    "1ab95c88-22f0-4e90-a0ac-7dd6ef64e112"
  ]
}
```

---

## 5. Troubleshooting
### Invalid programme selection error
- One or more programme UUIDs are inactive or outside institution scope.
- Re-select programmes from current institution catalogue.

### Selection exceeds template limit
- Reduce selected programmes to template max_programmes_per_application.

### Submission blocked for AP-005
- Ensure at least one programme is selected when AP-005 is enabled.

---

## 6. Audit and Compliance
- Programme selections are stored on draft records under selected_programme_uuids.
- Institution scoping is enforced in validation and access controls.
- No cross-tenant programme reference is permitted.
