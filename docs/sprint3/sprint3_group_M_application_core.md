# Sprint 3 - Group M: Application Core Foundation

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** M  
**Module:** Application and Admission Pipeline  
**Req IDs:** CRM-AP-001 to CRM-AP-007  
**Status:** Completed (AP-001 to AP-007 Completed)

---

## Objective

Build the application form foundation needed for downstream pipeline, fee, and document flows.

## In Scope

1. Configurable multi-step application form builder.
2. Section and field-level logic support.
3. Save-and-resume flow.
4. Application fee trigger point integration.
5. Multi-programme application support.
6. Mandatory vs optional field rules.
7. Mobile-responsive application UX baseline.

## Dependencies

1. Existing lead and enquiry data model from Sprint 1.
2. Institution and campus scoping middleware.
3. Existing consent and DPDP baseline rules.

## Design Notes

1. Use web controllers and views for CRM staff workflows.
2. Keep integration APIs under versioned API routes only.
3. Ensure all application records remain tenant-scoped.
4. Emit state events for submit, save, resume, and completion checks.

## Deliverables

1. Group implementation log updates.
2. User manual section for applicant and counsellor workflows.
3. Group M test cases document.
4. Master tracker status and remarks update.

## Acceptance Gates

1. Applicant can start, save, resume, and submit forms.
2. Form completeness thresholds enforce AP-007.
3. Multi-programme apply flow works and is auditable.
4. No cross-tenant record visibility.

## Risks and Mitigation

1. Dynamic form complexity:
Mitigation: lock field schema version per submitted application.
2. Partial save consistency:
Mitigation: transactional saves with draft status checkpoints.

## Exit Criteria

1. AP-001 to AP-007 marked completed in tracker.
2. User manual and test cases published.
3. QA sign-off recorded.

---

## Implementation Log

### 2026-04-14 - CRM-AP-001 (Completed End-to-End)

1. Added AP-001 backend foundation for configurable multi-step application form builder:
	- Migration: `application_form_templates` with tenant scope, sections JSON, progression rules, settings, versioning, and completeness threshold.
	- Model: `ApplicationFormTemplate` with `InstitutionScope`, `HasUuids`, `SoftDeletes`, and audit observer.
	- Service/Repository layer: create, update, list, archive, unique slug generation.
	- Policy + provider wiring for RBAC and container bindings.
2. Added web management flow under `/crm/applications/forms`:
	- Controller: `ApplicationFormTemplateWebController`.
	- Validation requests for create/update with section, field and logic schema rules.
	- Routes in `routes/web.php` with permission middleware.
3. Added integration API flow under `/api/v1/crm/application-form-templates`:
	- Controller: `ApplicationFormTemplateController` (Sanctum protected).
	- Resource: `ApplicationFormTemplateResource`.
	- Versioned API routes with UUID route binding and response envelope.
4. Added initial Blade UI for AP-001:
	- Template list screen.
	- Multi-step builder screen with section/field/progression-rule configuration.
5. Added AP-001 feature tests:
	- Create template via web route.
	- Institution isolation on edit route.
	- API create/list/show isolation/update test coverage.

### Pending Scope (Group M)

1. Group M implementation closed.
2. No open AP requirements pending in Group M.
3. Maintain regression coverage while starting Group N dependencies.

### 2026-04-14 - CRM-AP-002 (Completed End-to-End)

1. Added AP-002 schema support enforcement in application form template validation:
	- Required section coverage validation for: `personal_details`, `academic_history`, `entrance_exam_scores`, `co_curricular_activities`, `declarations`, and `digital_signature`.
	- Digital signature enforcement via at least one field with `type = signature`.
	- Added duplicate guardrails for section IDs and field IDs within each section.
2. Upgraded builder defaults to AP-002-ready baseline in web UI:
	- Create/Edit form now preloads all AP-002 baseline sections with representative starter fields.
	- Declarations and digital signature sections are available by default for counsellor configuration.
3. Extended AP test coverage:
	- API: added negative tests for missing required AP-002 section and missing digital signature field.
	- Web: added validation failure test when AP-002 required sections are omitted.
4. Added AP-002 readiness preview in form builder UI:
	- Live readiness indicator for required section coverage.
	- Live checks for signature field presence and duplicate section/field IDs.
5. Published AP-002 documentation artifacts:
	- User manual: `docs/usermanual/application-form-ap002.md`.
	- Group M AP-002 test cases: `docs/sprint3/test-cases/sprint3_group_M_test_cases.md`.

### 2026-04-14 - CRM-AP-003 (Completed End-to-End)

1. Added AP-003 save-and-resume backend persistence:
	- Migration: `application_form_drafts` with tenant scope, resume token, status, progress tracking, expiry window, and soft deletes.
	- Model: `ApplicationFormDraft` with `InstitutionScope`, `HasUuids`, `SoftDeletes`, and encrypted `form_data` cast.
	- Enum: `ApplicationFormDraftStatus` for draft lifecycle states.
2. Added AP-003 service and repository layer:
	- `ApplicationFormDraftService` for draft create, save, and resume flows.
	- Template guard enforces `settings.allow_save_and_resume = true` before creating drafts.
	- Expiry and editable-state validation for draft updates and resume.
3. Added AP-003 API endpoints under `/api/v1/crm`:
	- Create draft by template UUID.
	- Save draft by draft UUID.
	- Resume by resume token.
	- Retrieve draft by UUID.
4. Added AP-003 policy and provider wiring:
	- `ApplicationFormDraftPolicy` for tenant-safe view/edit access.
	- Repository binding and policy registration in `CrmApplicationServiceProvider`.
5. Added AP-003 web configuration support:
	- Builder UI now includes `Allow save and resume` setting in template create/edit screen.
6. Added AP-003 automated test coverage:
	- New API feature tests for create, validation, update, resume, and cross-tenant isolation.
7. Closed AP-003 submission loop for end-to-end readiness:
	- Added draft submission endpoint and service flow with completeness threshold validation.
	- Draft submission now transitions status from `draft` to `submitted` and records submission timestamp.
	- Added validation tests for successful submit and rejected submit below threshold.
8. Added CRM web app fill/save/resume/submit flow:
	- New web controller flow for template fill, draft resume, save, and submit under `/crm/applications/...` routes.
	- Added staff-facing fill Blade view with section-field rendering and draft prefill support.
9. Added public applicant fill/save/resume/submit flow:
	- New public controller and requests for `/apply/{slug}` and `/apply/resume/{resumeToken}` routes.
	- Added public fill Blade view for no-login draft save/resume and final submit.
10. Added AP-003 web/public automated coverage:
	- `ApplicationFormDraftWebTest` for CRM staff browser flow.
	- `PublicApplicationFormDraftTest` for public applicant draft/resume/submit flow.

### 2026-04-14 - CRM-AP-004 (Completed End-to-End)

1. Added AP-004 configurable application fee settings at template level:
	- Added template settings support for `application_fee_enabled`, `application_fee_amount`, and `application_fee_currency`.
	- Added validation guardrails to require positive fee amount when AP-004 is enabled.
2. Added AP-004 draft fee persistence and lifecycle fields:
	- Migration adds fee amount/currency/status, payment reference, gateway, and paid timestamp to `application_form_drafts`.
	- Draft resource and model now expose AP-004 fee status for API and web flows.
3. Enforced fee-before-submit submission gate in service layer:
	- Draft submission now blocks when fee status is `pending`.
	- Added service action to mark fee paid with transaction reference and gateway metadata.
4. Added AP-004 fee payment actions across all channels:
	- API endpoint for draft fee payment under `/api/v1/crm/application-form-drafts/{uuid}/fee/pay`.
	- CRM web route/action to pay fee from draft resume page.
	- Public applicant route/action to pay fee from resume link page.
5. Added AP-004 UI support:
	- Builder screen now captures AP-004 fee configuration.
	- CRM and public fill screens display fee status and Pay Fee action.
6. Added AP-004 automated test coverage:
	- API: fee-required submission and successful pay-then-submit path.
	- CRM web: fee-required submission and pay-then-submit path.
	- Public web: fee-required submission and pay-then-submit path.

### 2026-04-14 - CRM-AP-005 (Completed End-to-End)

1. Added AP-005 template configuration for multi-programme applications:
	- Added settings support for `allow_multi_programme_applications` and `max_programmes_per_application`.
	- Added validation to require max >= 2 when multi-programme applications are enabled.
2. Added AP-005 draft programme selection persistence:
	- Added `selected_programme_uuids` JSON column to application drafts.
	- Exposed selected programme UUIDs in API resource responses.
3. Added AP-005 service-level programme validation and submission guardrails:
	- Programme selections are validated against active institution-scoped programme catalogue.
	- Selection count is enforced against template max programme limit.
	- Submission enforces programme selection when AP-005 setting is enabled.
4. Added AP-005 UI support in web and public flows:
	- CRM and public fill screens now show programme multi-select controls.
	- Selected programmes are saved and reused during resume and submit operations.
5. Added AP-005 automated coverage:
	- API, CRM web, and public tests for multi-programme selection and successful submission.

### 2026-04-14 - CRM-AP-006 (Completed End-to-End)

1. Added AP-006 mobile-optimised enforcement in template validation:
	- Create and update template requests now enforce `settings.mobile_optimised = true`.
	- Non-mobile templates are rejected with validation error for AP-006 compliance.
2. Added AP-006 service-layer runtime gate across draft lifecycle:
	- Draft create, save, submit, and fee payment flows now validate mobile optimisation before processing.
	- Service returns validation error when template mobile optimisation is disabled.
3. Added AP-006 CRM web flow guardrails:
	- Staff fill page blocks non-mobile templates and redirects to forms list with error.
	- Resume action blocks non-mobile templates for existing drafts.
4. Added AP-006 public applicant flow guardrails:
	- Public apply route no longer exposes templates with mobile optimisation disabled.
5. Added AP-006 automated coverage:
	- API test for draft creation rejection on non-mobile templates.
	- CRM web test for fill route blocking when template is not mobile-optimised.
	- Public test for 404 on non-mobile template slug.

### 2026-04-14 - CRM-AP-007 (Completed End-to-End)

1. Completed AP-007 mandatory vs optional field handling in runtime submission flow:
	- Submission now enforces `required = true` fields for submitted sections.
	- Required checkbox fields must be truthy; required text/signature fields cannot be blank.
2. Preserved template-defined completeness threshold behavior:
	- Existing minimum completeness threshold gate remains active at submit.
	- AP-007 mandatory field failures return field-specific validation errors.
3. Added AP-007 automated coverage:
	- API draft submission test verifies required field enforcement for AP-007.
