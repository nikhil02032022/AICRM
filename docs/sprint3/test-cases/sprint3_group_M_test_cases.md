# Sprint 3 Group M - Test Cases
## AP-002: Mandatory Section Support and Digital Signature
**BRD:** CRM-AP-002
**Date:** April 2026

---

## Coverage Summary
- AP2-TC-001 to AP2-TC-008
- Scope: web builder, API create/update validation, tenant isolation regression

---

## Test Cases

### AP2-TC-001 - Create template with full AP-002 sections
- Preconditions: User has crm.applications.create and belongs to institution.
- Steps:
1. Open create template page.
2. Keep all default AP-002 sections.
3. Submit form.
- Expected Result:
1. Template is created.
2. Success message shown.
3. Record is stored under user institution.

### AP2-TC-002 - API create with compliant payload
- Preconditions: Sanctum-authenticated user with create permission.
- Steps:
1. POST /api/v1/crm/application-form-templates with all required AP-002 sections and signature field.
- Expected Result:
1. HTTP 201 returned.
2. success true in response envelope.
3. Stored template slug and institution match payload/user.

### AP2-TC-003 - Reject when required AP-002 section missing
- Preconditions: Authenticated and authorized user.
- Steps:
1. Submit payload without co_curricular_activities.
- Expected Result:
1. HTTP 422 returned.
2. Validation error on sections.

### AP2-TC-004 - Reject when no signature type field exists
- Preconditions: Authenticated and authorized user.
- Steps:
1. Submit payload where digital_signature section contains only text field.
- Expected Result:
1. HTTP 422 returned.
2. Validation error on sections.

### AP2-TC-005 - Reject duplicate section IDs
- Preconditions: Authenticated and authorized user.
- Steps:
1. Submit payload with repeated personal_details section id.
- Expected Result:
1. Validation error on sections.
2. Save blocked.

### AP2-TC-006 - Reject duplicate field IDs in same section
- Preconditions: Authenticated and authorized user.
- Steps:
1. Add two fields with same id in declarations section.
2. Submit form.
- Expected Result:
1. Validation error on sections.
2. Save blocked.

### AP2-TC-007 - AP-002 readiness preview visibility
- Preconditions: User has crm.applications.create permission.
- Steps:
1. Open /crm/applications/forms/create.
- Expected Result:
1. AP-002 Readiness Preview card appears.
2. Required section IDs are listed.

### AP2-TC-008 - Tenant isolation unchanged
- Preconditions: Two institutions with separate admin users.
- Steps:
1. Institution A creates template.
2. Institution B user accesses edit route for A template.
- Expected Result:
1. Route returns not found.
2. No cross-tenant visibility.

---

## Automation Status
- Implemented in feature tests:
1. tests/Feature/CRM/Api/ApplicationFormTemplateApiTest.php
2. tests/Feature/CRM/Application/ApplicationFormBuilderWebTest.php

---

## AP-003: Save and Resume Draft Flow
**BRD:** CRM-AP-003

### AP3-TC-001 - Create draft when template allows save and resume
- Preconditions: Authenticated API user with application create permission and template setting allow_save_and_resume=true.
- Steps:
1. POST draft create endpoint for template UUID with partial form data.
- Expected Result:
1. HTTP 201 returned.
2. Draft UUID and resume token returned.
3. Draft stored in institution scope with status draft.

### AP3-TC-002 - Reject draft creation when template does not allow save and resume
- Preconditions: Template setting allow_save_and_resume=false.
- Steps:
1. POST draft create endpoint.
- Expected Result:
1. HTTP 422 returned.
2. Validation error for settings.allow_save_and_resume.

### AP3-TC-003 - Save draft progress by UUID
- Preconditions: Existing draft in draft status.
- Steps:
1. PUT update endpoint with new current section, progress, and form data.
- Expected Result:
1. HTTP 200 returned.
2. Updated progress and payload values are persisted.

### AP3-TC-004 - Resume draft by token in same tenant
- Preconditions: Existing non-expired draft and valid token in same institution.
- Steps:
1. GET resume endpoint with resume token.
- Expected Result:
1. HTTP 200 returned.
2. Matching draft payload is returned.

### AP3-TC-005 - Resume token from another tenant is not accessible
- Preconditions: Draft belongs to institution A; requester belongs to institution B.
- Steps:
1. GET resume endpoint using institution A token.
- Expected Result:
1. HTTP 404 returned.
2. No cross-tenant data leak.

### AP3-TC-006 - Submit draft when completeness threshold is met
- Preconditions: Existing draft in draft status with template minimum completeness set.
- Steps:
1. POST submit endpoint for draft UUID with progress_percentage >= minimum_completeness_percentage.
- Expected Result:
1. HTTP 200 returned.
2. Draft status changes to submitted.
3. submitted_at timestamp is recorded.

### AP3-TC-007 - Reject submit below completeness threshold
- Preconditions: Existing draft in draft status with low progress.
- Steps:
1. POST submit endpoint with progress_percentage below template minimum.
- Expected Result:
1. HTTP 422 returned.
2. Validation error on progress_percentage.
3. Draft remains in draft status.

### AP3-TC-008 - CRM web staff saves draft from fill screen
- Preconditions: Staff user with application create permission and AP-003-enabled template.
- Steps:
1. Open /crm/applications/forms/{uuid}/fill.
2. Enter partial applicant details.
3. Click Save Draft.
- Expected Result:
1. Redirect to /crm/applications/drafts/{uuid}/resume.
2. Draft record created with status draft.
3. Progress and form payload persisted.

### AP3-TC-009 - CRM web staff submits resumed draft
- Preconditions: Existing AP-003 draft in staff web flow.
- Steps:
1. Open resume page for draft UUID.
2. Update fields and click Submit.
- Expected Result:
1. Draft transitions to submitted.
2. submitted_at timestamp recorded.
3. Success flash message displayed.

### AP3-TC-010 - Public applicant saves and resumes by token
- Preconditions: AP-003-enabled template with slug.
- Steps:
1. Open /apply/{slug}.
2. Fill partial data and click Save Draft.
3. Re-open /apply/resume/{resumeToken}.
- Expected Result:
1. Draft is accessible via token URL.
2. Existing values are prefilled.
3. Save can be repeated without creating cross-tenant leak.

### AP3-TC-011 - Public applicant submits completed draft
- Preconditions: Public draft exists and meets completeness threshold.
- Steps:
1. Open resume URL.
2. Submit final payload.
- Expected Result:
1. Draft transitions to submitted.
2. submitted_at is recorded.
3. Success status returned in response payload.

### AP-003 Automation Status
- Implemented in feature tests:
1. tests/Feature/CRM/Api/ApplicationFormDraftApiTest.php
2. tests/Feature/CRM/Application/ApplicationFormDraftWebTest.php
3. tests/Feature/CRM/Application/PublicApplicationFormDraftTest.php

---

## AP-004: Application Fee at Submission
**BRD:** CRM-AP-004

### AP4-TC-001 - Template enables configurable application fee
- Preconditions: Staff user with template create/edit permission.
- Steps:
1. Open application template create/edit screen.
2. Enable application fee.
3. Enter positive fee amount and currency.
4. Save template.
- Expected Result:
1. Template is saved.
2. AP-004 fee settings are persisted.

### AP4-TC-002 - Reject AP-004 configuration with non-positive fee amount
- Preconditions: Template create/edit payload with fee enabled.
- Steps:
1. Set settings.application_fee_enabled=true.
2. Set settings.application_fee_amount=0.
3. Submit create/update request.
- Expected Result:
1. Validation fails.
2. Error returned for settings.application_fee_amount.

### AP4-TC-003 - API submit blocked until fee is paid
- Preconditions: Draft created from AP-004-enabled template with fee status pending.
- Steps:
1. Call submit endpoint for draft.
- Expected Result:
1. HTTP 422 returned.
2. Validation error on application_fee_status.

### AP4-TC-004 - API pay fee then submit succeeds
- Preconditions: AP-004 draft pending fee payment.
- Steps:
1. Call fee pay endpoint with gateway and transaction reference.
2. Submit draft endpoint.
- Expected Result:
1. Fee status becomes paid.
2. Draft submit succeeds when completeness threshold is met.

### AP4-TC-005 - CRM web submit blocked until fee payment
- Preconditions: Staff resumes AP-004-enabled draft in pending fee status.
- Steps:
1. Click Submit Application.
- Expected Result:
1. Submission blocked with application_fee_status error.

### AP4-TC-006 - CRM web pay fee then submit succeeds
- Preconditions: AP-004 draft in pending fee status.
- Steps:
1. Click Pay Fee Now.
2. Click Submit Application.
- Expected Result:
1. Fee status set to paid.
2. Draft transitions to submitted.

### AP4-TC-007 - Public submit blocked until fee payment
- Preconditions: Public resume draft in pending fee status.
- Steps:
1. Submit application from resume URL.
- Expected Result:
1. Submission blocked with application_fee_status error.

### AP4-TC-008 - Public pay fee then submit succeeds
- Preconditions: Public resume draft pending fee payment.
- Steps:
1. Click Pay Fee Now on resume page.
2. Submit application.
- Expected Result:
1. Fee status set to paid.
2. Draft transitions to submitted.

### AP-004 Automation Status
- Implemented in feature tests:
1. tests/Feature/CRM/Api/ApplicationFormDraftApiTest.php
2. tests/Feature/CRM/Application/ApplicationFormDraftWebTest.php
3. tests/Feature/CRM/Application/PublicApplicationFormDraftTest.php

---

## AP-005: Simultaneous Multi-Programme Application
**BRD:** CRM-AP-005

### AP5-TC-001 - Enable AP-005 in template settings
- Preconditions: Staff user with template create/edit permission.
- Steps:
1. Enable allow_multi_programme_applications.
2. Set max_programmes_per_application >= 2.
3. Save template.
- Expected Result:
1. Template is saved with AP-005 settings.

### AP5-TC-002 - Reject invalid AP-005 max setting
- Preconditions: AP-005 enabled in settings payload.
- Steps:
1. Set allow_multi_programme_applications=true.
2. Set max_programmes_per_application=1.
3. Submit request.
- Expected Result:
1. Validation error on settings.max_programmes_per_application.

### AP5-TC-003 - API accepts multiple programmes within limit
- Preconditions: Active programmes exist for institution.
- Steps:
1. Create draft with two programme UUIDs.
2. Submit with valid selected programmes.
- Expected Result:
1. Draft stores selected_programme_uuids.
2. Draft submit succeeds if other gates pass.

### AP5-TC-004 - API rejects invalid/inactive/out-of-tenant programmes
- Preconditions: Mixed valid and invalid programme UUID input.
- Steps:
1. Submit draft update with invalid programme UUID.
- Expected Result:
1. Validation error on programme_uuids.

### AP5-TC-005 - CRM web supports multi-programme submit
- Preconditions: AP-005 enabled template and active institution programmes.
- Steps:
1. Resume draft in CRM web flow.
2. Select multiple programmes.
3. Submit draft.
- Expected Result:
1. Draft stores selected programmes.
2. Submit succeeds.

### AP5-TC-006 - Public flow supports multi-programme submit
- Preconditions: AP-005 enabled template and active institution programmes.
- Steps:
1. Resume draft via public token URL.
2. Select multiple programmes.
3. Submit draft.
- Expected Result:
1. Draft stores selected programmes.
2. Submit succeeds.

### AP-005 Automation Status
- Implemented in feature tests:
1. tests/Feature/CRM/Api/ApplicationFormDraftApiTest.php
2. tests/Feature/CRM/Application/ApplicationFormDraftWebTest.php
3. tests/Feature/CRM/Application/PublicApplicationFormDraftTest.php

---

## AP-006: Mobile-Responsive and Mobile-Optimised Application Flow
**BRD:** CRM-AP-006

### AP6-TC-001 - Enforce mobile_optimised setting on template create/update
- Preconditions: Staff user with template create/edit permission.
- Steps:
1. Submit create or update payload with settings.mobile_optimised=false.
- Expected Result:
1. Validation fails with settings.mobile_optimised error.

### AP6-TC-002 - API blocks draft creation for non-mobile template
- Preconditions: Template exists with settings.mobile_optimised=false.
- Steps:
1. Call POST draft create endpoint for template UUID.
- Expected Result:
1. HTTP 422 returned.
2. Validation error on settings.mobile_optimised.

### AP6-TC-003 - CRM web fill route blocks non-mobile template
- Preconditions: Authenticated staff user and template with mobile_optimised=false.
- Steps:
1. Open /crm/applications/forms/{uuid}/fill.
- Expected Result:
1. Redirect to forms list.
2. Error message indicates mobile optimisation is required.

### AP6-TC-004 - Public apply route does not expose non-mobile template
- Preconditions: Active template exists with slug and mobile_optimised=false.
- Steps:
1. Open /apply/{slug}.
- Expected Result:
1. HTTP 404 returned.
2. Template remains inaccessible to applicants.

### AP-006 Automation Status
- Implemented in feature tests:
1. tests/Feature/CRM/Api/ApplicationFormDraftApiTest.php
2. tests/Feature/CRM/Application/ApplicationFormDraftWebTest.php
3. tests/Feature/CRM/Application/PublicApplicationFormDraftTest.php

---

## AP-007: Mandatory vs Optional Fields and Completeness Threshold
**BRD:** CRM-AP-007

### AP7-TC-001 - Institution configures mandatory and optional fields in template builder
- Preconditions: Staff user with template create/edit permission.
- Steps:
1. Set selected fields with required=true and others as optional.
2. Save template configuration.
- Expected Result:
1. Field-level required flags are persisted in template sections.

### AP7-TC-002 - Institution configures minimum completeness threshold
- Preconditions: Staff user with template create/edit permission.
- Steps:
1. Set minimum_completeness_percentage in template.
2. Save template.
- Expected Result:
1. Threshold value persists and is used by submit workflow.

### AP7-TC-003 - Submit fails when mandatory field is blank in submitted section
- Preconditions: Draft exists with AP-007 template and required fields configured.
- Steps:
1. Submit draft payload where a required field is blank in provided form_data section.
- Expected Result:
1. HTTP 422 returned.
2. Validation error returned for specific mandatory field path.

### AP7-TC-004 - Submit succeeds when mandatory fields are satisfied and threshold is met
- Preconditions: Draft has required fields populated and meets completeness threshold.
- Steps:
1. Submit draft.
- Expected Result:
1. Submission succeeds.
2. Draft status transitions to submitted.

### AP-007 Automation Status
- Implemented in feature tests:
1. tests/Feature/CRM/Api/ApplicationFormDraftApiTest.php
