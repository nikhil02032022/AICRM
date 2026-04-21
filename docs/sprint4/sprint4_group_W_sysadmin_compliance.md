# Sprint 4 - Group W: System Administration, Compliance and Sprint Closure

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** W
**Module:** System Administration (SA) + Compliance/Regulatory (CR) + Alumni Lifecycle Bridge (AL-001)
**Req IDs:** SA-001–SA-006, SA-008, SA-009, SA-012 | CR-001–CR-010 | AL-001
**Status:** Pending
**Dependencies:** All prior sprint models; must be completed before final Group V regression (tenancy scoping affects all dashboard queries)

---

## Objective

Deliver the multi-institution tenancy layer, global audit trail, academic year management, data import/export, system configuration, custom fields, notification templates, backup/restore, full DPDP Act 2023 and TRAI compliance controls, and the alumni pipeline seed trigger — completing Phase 1 Sprint 4 and closing all Must-Have requirements.

## In Scope

### System Administration (SA)
- SA-001: Multi-institution support with complete data segregation.
- SA-002: Multi-campus support within a single institution.
- SA-003: Academic year / admission cycle management with rollover.
- SA-004: Full audit trail for all CRM data changes (who changed what, when).
- SA-005: Data import and export (leads, applications, contacts) in CSV/Excel.
- SA-006: System configuration (business hours, timezone, locale, institution branding).
- SA-008: Custom field management for leads, applications, and students.
- SA-009: Email and notification template management.
- SA-012: Backup and restore with configurable frequency.

### Compliance (CR — DPDP Act 2023 / TRAI / DoT)
- CR-001: Explicit consent capture at point of lead creation.
- CR-002: Consent records stored with timestamp, IP address, and form version.
- CR-003: Opt-out/unsubscribe honoured within 24 hours and logged.
- CR-004: Right-to-access — applicant can request a copy of their stored data from the portal.
- CR-005: Right-to-erasure — verified erasure requests anonymise PII within 30 days.
- CR-006: All personal data of Indian residents stored on India-hosted servers (config enforcement).
- CR-007: Call recording consent notification to caller (extends CC-018).
- CR-008: SMS communications use DLT-registered templates (extends CC-008).
- CR-009: Data Processing Agreement available for institutions as Data Fiduciaries.
- CR-010: Breach notification workflow — alert institution admin within 72h of detected breach.

### Alumni Lifecycle Bridge
- AL-001: Enrolled student records auto-populate the alumni pipeline upon programme completion.

## Out of Scope

- SA-007 (Workflow and automation template library) — completed Sprint 2 Group K.
- SA-010 (Integration credential management) — completed Sprint 1 Group C.
- SA-011 (System health monitoring dashboard) — completed Sprint 2 Group K.
- AL-002, AL-003, AL-004 — Should Have / Could Have, deferred to Sprint 5.

## Dependencies

1. All CRM models from Sprints 1–4 (for global audit observer and custom fields).
2. `Lead` model for consent and erasure (Sprint 1).
3. `CallRecording` model for consent notification (TC-008, Sprint 2).
4. `Communication` and SMS gateway for DLT template validation (CC-008, Sprint 1).
5. `Application` and `Student` models for alumni trigger (Sprint 3).
6. Laravel Backup package (`spatie/laravel-backup`) for SA-012.

## Design Notes

1. Tenancy (SA-001) is implemented as a global scope (`InstitutionScope`) applied automatically to all CRM models via a trait. `institution_id` is resolved from the authenticated user's institution.
2. Campus scoping (SA-002) is a secondary scope applied where campus-level segregation is required (leads, programmes, counsellors).
3. Audit trail (SA-004) uses a global `AuditObserver` registered on all CRM models via `ObservesAudit` trait; writes to `audit_logs` table.
4. Custom fields (SA-008) are stored as EAV (Entity-Attribute-Value) in a `custom_field_values` table keyed by model type and model ID. Field definitions are per institution.
5. Erasure (CR-005) replaces PII fields with anonymised placeholders (`[ERASED]`, null for non-required) via `ErasePersonalDataJob`; does not delete the record (preserves referential integrity and aggregate counts).
6. Data residency (CR-006) is enforced via `config/data_residency.php`; a middleware check prevents file uploads to non-India storage drivers in production.
7. Breach notification (CR-010) is manually triggered by an admin; creates a `SecurityIncident` record and dispatches `BreachNotificationJob` which sends emails to institution admins and system administrators.
8. Alumni trigger (AL-001) fires from a `GraduationObserver` on the `Application` model when status → `graduated`; calls `AlumniPipelineService::enqueue()`.

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for system administration, compliance features, and admin controls.
3. Group W test cases document (`test-cases/sprint4_group_W_test_cases.md`).
4. Master tracker status and remarks update.
5. Sprint 4 closure — all BRD coverage tracker rows updated.

## Acceptance Gates

1. Two institutions with separate users cannot access each other's leads, applications, or reports.
2. Audit log records every field-level change with user ID, timestamp, old value, and new value.
3. Academic year rollover command migrates pipeline stage counters and resets seat counts.
4. CSV import for leads validates headers, reports errors per row, and imports valid rows atomically.
5. Consent is captured and stored with timestamp and IP at lead creation; opt-out job runs within 24h of request.
6. PII erasure job marks PII fields as `[ERASED]` and logs the erasure event to audit trail.
7. Breach notification email is sent to all institution admins within a configured delay after incident creation.
8. Alumni pipeline record is created when a student's application status changes to `graduated`.
9. Custom fields appear on lead and application forms for the institution that configured them.

## Risks and Mitigation

1. Global audit observer performance under high write load:
   Mitigation: write audit logs asynchronously via a queued job; batch insert where possible.
2. Erasure impacting aggregate reports:
   Mitigation: erasure replaces PII text fields only; aggregate counters and IDs remain intact for reporting.
3. Tenancy scope missing from a new model:
   Mitigation: create an automated test that queries each CRM model without an authenticated scope and asserts it returns zero rows (cross-tenant leak detection test).

## Exit Criteria

1. SA-001–006, SA-008, SA-009, SA-012, CR-001–010, AL-001 marked completed in master tracker.
2. ~25 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. Sprint 4 BRD coverage tracker fully updated.
5. QA sign-off recorded.

---

## File Manifest

### Migrations
- `create_institutions_table.php` — name, slug, logo_path, primary_colour, domain, timezone, locale, business_hours_json, status
- `create_campuses_table.php` — institution_id, name, code, city, status
- `create_academic_years_table.php` — institution_id, label, start_date, end_date, is_active, rolled_over_from_id
- `create_audit_logs_table.php` — institution_id, user_id, model_type, model_id, event, changed_fields_json, ip_address, user_agent, created_at
- `create_system_configs_table.php` — institution_id, key, value, type (string/json/boolean/integer), updated_by
- `create_custom_fields_table.php` — institution_id, entity_type (lead/application/student), field_key, label, field_type, options_json, is_required, is_active, sort_order
- `create_custom_field_values_table.php` — custom_field_id, entity_type, entity_id, value_text, value_json
- `create_notification_templates_table.php` — institution_id, channel (email/sms/whatsapp), name, subject, body, merge_tags_json, is_active
- `create_consent_records_table.php` — lead_id, institution_id, consent_type, form_version, ip_address, user_agent, consented_at, revoked_at
- `create_opt_out_logs_table.php` — lead_id, institution_id, channel (email/sms/whatsapp/all), requested_at, processed_at, processed_by_job
- `create_data_access_requests_table.php` — lead_id, institution_id, requested_at, processed_at, delivery_method, status
- `create_pii_erasure_requests_table.php` — lead_id, institution_id, requested_at, scheduled_erasure_at, erased_at, erased_by_job, status
- `create_security_incidents_table.php` — institution_id, reported_by, incident_type, description, detected_at, notified_at, status, documentation_json
- `create_alumni_pipeline_table.php` — lead_id, application_id, institution_id, programme_id, graduated_at, alumni_status

### Enums
- `App\Enums\CRM\Admin\AcademicYearStatus` — Active, Closed, Archived
- `App\Enums\CRM\Admin\CustomFieldType` — Text, Textarea, Number, Date, Select, MultiSelect, Checkbox, File
- `App\Enums\CRM\Admin\NotificationChannel` — Email, SMS, WhatsApp
- `App\Enums\CRM\Compliance\ConsentType` — MarketingCommunication, DataProcessing, CallRecording
- `App\Enums\CRM\Compliance\PiiErasureStatus` — Pending, Scheduled, Erased, Failed
- `App\Enums\CRM\Compliance\SecurityIncidentStatus` — Reported, Investigating, Notified, Resolved

### Models
- `App\Models\CRM\Admin\Institution`
- `App\Models\CRM\Admin\Campus`
- `App\Models\CRM\Admin\AcademicYear`
- `App\Models\CRM\Admin\AuditLog`
- `App\Models\CRM\Admin\SystemConfig`
- `App\Models\CRM\Admin\CustomField`
- `App\Models\CRM\Admin\CustomFieldValue`
- `App\Models\CRM\Admin\NotificationTemplate`
- `App\Models\CRM\Compliance\ConsentRecord`
- `App\Models\CRM\Compliance\OptOutLog`
- `App\Models\CRM\Compliance\DataAccessRequest`
- `App\Models\CRM\Compliance\PiiErasureRequest`
- `App\Models\CRM\Compliance\SecurityIncident`
- `App\Models\CRM\Alumni\AlumniPipeline`

### Traits
- `App\Traits\CRM\HasInstitutionScope` — adds `InstitutionScope` global scope to CRM models (SA-001)
- `App\Traits\CRM\HasCampusScope` — adds campus-level secondary scope (SA-002)
- `App\Traits\CRM\ObservesAudit` — registers `AuditObserver` on model boot (SA-004)
- `App\Traits\CRM\HasCustomFields` — provides `customFields()` and `customFieldValues()` relationships (SA-008)

### Scopes
- `App\Models\Scopes\CRM\InstitutionScope` — global scope applying `where('institution_id', auth()->user()->institution_id)`
- `App\Models\Scopes\CRM\CampusScope`

### Services
- `App\Services\CRM\Admin\TenancyService` — resolve current institution and campus from authenticated context
- `App\Services\CRM\Admin\AcademicYearService` — create, activate, rollover (seat counters, pipeline state reset)
- `App\Services\CRM\Admin\DataImportService` — validate CSV/Excel headers, row-by-row import for leads, applications, contacts; error report generation
- `App\Services\CRM\Admin\DataExportService` — export leads, applications, contacts to CSV/Excel
- `App\Services\CRM\Admin\SystemConfigService` — get/set per-institution config values
- `App\Services\CRM\Admin\CustomFieldService` — CRUD for field definitions, value rendering on forms
- `App\Services\CRM\Admin\NotificationTemplateService` — CRUD, merge tag resolution
- `App\Services\CRM\Compliance\ConsentService` — capture consent, record timestamp and IP, check opt-out status
- `App\Services\CRM\Compliance\OptOutService` — process opt-out requests, update lead preferences, log
- `App\Services\CRM\Compliance\DataAccessService` — compile applicant data package for right-to-access (CR-004)
- `App\Services\CRM\Compliance\PiiErasureService` — schedule erasure, anonymise PII fields (CR-005)
- `App\Services\CRM\Compliance\DataResidencyService` — validate storage driver and region config (CR-006)
- `App\Services\CRM\Compliance\DltTemplateValidatorService` — check SMS templates against DLT registry before send (CR-008)
- `App\Services\CRM\Compliance\BreachNotificationService` — create incident, dispatch notification job (CR-010)
- `App\Services\CRM\Alumni\AlumniPipelineService` — create alumni record from enrolled student on graduation event (AL-001)

### Observers
- `App\Observers\CRM\Admin\AuditObserver` — registers on all CRM models via `ObservesAudit` trait; queues `WriteAuditLogJob`
- `App\Observers\CRM\Alumni\GraduationObserver` — fires on `Application` status → `graduated`; calls `AlumniPipelineService::enqueue()`

### Jobs
- `App\Jobs\CRM\Admin\WriteAuditLogJob` — async audit log write
- `App\Jobs\CRM\Admin\DataImportJob` — background lead/application import from uploaded file
- `App\Jobs\CRM\Admin\DatabaseBackupJob` — triggered by scheduler; delegates to `spatie/laravel-backup`
- `App\Jobs\CRM\Compliance\ProcessOptOutJob` — processes queued opt-out requests within 24h SLA
- `App\Jobs\CRM\Compliance\ErasePersonalDataJob` — runs on schedule; anonymises PII for approved erasure requests
- `App\Jobs\CRM\Compliance\BreachNotificationJob` — sends 72h breach notification emails to institution admins

### Commands
- `App\Console\Commands\CRM\Admin\RolloverAcademicYear` — `crm:rollover-academic-year {institution} {new_year_label}`
- `App\Console\Commands\CRM\Compliance\GdprErase` — `crm:gdpr:erase {lead_id}` (manual trigger for individual erasure)

### Controllers (Web — `/crm/admin`)
- `App\Http\Controllers\CRM\Admin\InstitutionController`
- `App\Http\Controllers\CRM\Admin\CampusController`
- `App\Http\Controllers\CRM\Admin\AcademicYearController`
- `App\Http\Controllers\CRM\Admin\SystemConfigController`
- `App\Http\Controllers\CRM\Admin\CustomFieldController`
- `App\Http\Controllers\CRM\Admin\NotificationTemplateController`
- `App\Http\Controllers\CRM\Admin\DataImportController`
- `App\Http\Controllers\CRM\Admin\DataExportController`
- `App\Http\Controllers\CRM\Admin\BackupController` — trigger and list backups
- `App\Http\Controllers\CRM\Compliance\ConsentController` — view/download consent records
- `App\Http\Controllers\CRM\Compliance\OptOutController` — list, manual process
- `App\Http\Controllers\CRM\Compliance\DataAccessRequestController` — receive and process data access requests
- `App\Http\Controllers\CRM\Compliance\PiiErasureController` — receive and schedule erasure requests
- `App\Http\Controllers\CRM\Compliance\SecurityIncidentController` — create, view, update incidents
- `App\Http\Controllers\CRM\Compliance\DpaController` — download DPA PDF (CR-009)

### Views (Blade)
- `resources/views/crm/admin/institutions/` (index, create, edit)
- `resources/views/crm/admin/campuses/` (index, create, edit)
- `resources/views/crm/admin/academic-years/` (index, create, rollover)
- `resources/views/crm/admin/system-config/index.blade.php`
- `resources/views/crm/admin/custom-fields/` (index, create, edit)
- `resources/views/crm/admin/notification-templates/` (index, create, edit)
- `resources/views/crm/admin/data-import/index.blade.php`
- `resources/views/crm/admin/data-export/index.blade.php`
- `resources/views/crm/admin/backups/index.blade.php`
- `resources/views/crm/compliance/consent/index.blade.php`
- `resources/views/crm/compliance/opt-out/index.blade.php`
- `resources/views/crm/compliance/data-access/index.blade.php`
- `resources/views/crm/compliance/erasure/index.blade.php`
- `resources/views/crm/compliance/incidents/` (index, create, show)
- `resources/views/crm/compliance/dpa.blade.php`

### Middleware
- `App\Http\Middleware\CRM\DataResidencyCheck` — validates storage config on file upload routes (CR-006)
- `App\Http\Middleware\CRM\DltTemplateSmsCheck` — intercepts SMS dispatch, validates DLT registration (CR-008)

### Policies
- `App\Policies\CRM\Admin\InstitutionPolicy`
- `App\Policies\CRM\Admin\SystemConfigPolicy`
- `App\Policies\CRM\Compliance\CompliancePolicy`

### Seeders
- `Database\Seeders\CRM\Admin\InstitutionSeeder` — seed default institution and campus for development
- `Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder`
- `Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder`

### Tests
- `tests/Unit/CRM/Admin/TenancyServiceTest.php`
- `tests/Unit/CRM/Admin/AuditObserverTest.php`
- `tests/Unit/CRM/Admin/CustomFieldServiceTest.php`
- `tests/Unit/CRM/Compliance\ConsentServiceTest.php`
- `tests/Unit/CRM/Compliance/PiiErasureServiceTest.php`
- `tests/Unit/CRM/Compliance/DltTemplateValidatorServiceTest.php`
- `tests/Feature/CRM/Admin/InstitutionTenancyIsolationTest.php`
- `tests/Feature/CRM/Admin/AcademicYearRolloverTest.php`
- `tests/Feature/CRM/Admin/DataImportExportTest.php`
- `tests/Feature/CRM/Admin/BackupTest.php`
- `tests/Feature/CRM/Compliance/ConsentCaptureTest.php`
- `tests/Feature/CRM/Compliance/OptOutProcessingTest.php`
- `tests/Feature/CRM/Compliance/PiiErasureJobTest.php`
- `tests/Feature/CRM/Compliance/BreachNotificationTest.php`
- `tests/Feature/CRM/Alumni/AlumniPipelineTriggerTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| SA-001 | Multi-institution data segregation | `InstitutionScope`, `HasInstitutionScope` trait, `TenancyService` |
| SA-002 | Multi-campus support | `CampusScope`, `HasCampusScope` trait, `CampusController` |
| SA-003 | Academic year / admission cycle management | `AcademicYear` model, `AcademicYearService`, `crm:rollover-academic-year` command |
| SA-004 | Full audit trail | `AuditObserver`, `ObservesAudit` trait, `WriteAuditLogJob`, `audit_logs` table |
| SA-005 | Data import and export CSV/Excel | `DataImportService`, `DataExportService`, `DataImportJob` |
| SA-006 | System configuration | `SystemConfig` model, `SystemConfigService`, `SystemConfigController` |
| SA-008 | Custom field management | `CustomField` / `CustomFieldValue` models, `CustomFieldService`, `HasCustomFields` trait |
| SA-009 | Notification template management | `NotificationTemplate` model, `NotificationTemplateService`, `NotificationTemplateController` |
| SA-012 | Backup and restore | `DatabaseBackupJob`, `BackupController`, `spatie/laravel-backup` |
| CR-001 | Explicit consent at lead creation | `ConsentService::capture()`, `consent_records` table, lead creation form |
| CR-002 | Consent record with timestamp, IP, form version | `ConsentRecord` model fields: `consented_at`, `ip_address`, `form_version` |
| CR-003 | Opt-out honoured within 24h | `ProcessOptOutJob`, `OptOutLog` model, `OptOutController` |
| CR-004 | Right-to-access | `DataAccessService`, `DataAccessRequestController` |
| CR-005 | Right-to-erasure (30-day PII anonymisation) | `PiiErasureService`, `ErasePersonalDataJob`, `crm:gdpr:erase` command |
| CR-006 | Data residency enforcement (India) | `DataResidencyService`, `DataResidencyCheck` middleware, `config/data_residency.php` |
| CR-007 | Call recording consent notification | `ConsentService` extends CC-018 recording trigger |
| CR-008 | DLT-registered SMS template enforcement | `DltTemplateValidatorService`, `DltTemplateSmsCheck` middleware |
| CR-009 | Data Processing Agreement | `DpaController`, DPA PDF stored in `storage/dpa/` |
| CR-010 | Breach notification workflow (72h) | `SecurityIncident` model, `BreachNotificationService`, `BreachNotificationJob` |
| AL-001 | Alumni pipeline from enrolled students | `GraduationObserver`, `AlumniPipelineService`, `AlumniPipeline` model |

---

## Security Checklist

- [ ] `InstitutionScope` is applied to every CRM model — verified by cross-tenant leak detection test.
- [ ] Audit log table is append-only; no update/delete routes exposed (policy denies all except read).
- [ ] PII erasure runs asynchronously; erasure status is logged before and after each field replacement.
- [ ] Consent records are immutable after creation; opt-out creates a new `OptOutLog` record.
- [ ] DPA PDF download restricted to institution admin role.
- [ ] Breach notification emails are logged in `security_incidents.notified_at`; deduplication prevents duplicate sends.
- [ ] Data residency check blocks S3 uploads to non-`ap-south-1` (Mumbai) region in production.

---

## Sprint 4 Closure Checklist

- [ ] All 64 Sprint 4 Req IDs marked ✅ in master BRD coverage tracker.
- [ ] All group test suites passing (R: ~25, S: ~20, U: ~18, V: ~35, W: ~25 — total ~123 tests).
- [ ] Sprint 4 user manual published.
- [ ] All sprint4 group docs and test case docs committed.
- [ ] Phase 1 Must-Have BRD coverage verified at ≥95%.
- [ ] Sprint 5 scope (MB, EI, AL-002–004, AI remaining, NFR) documented and signed off.

---

## Implementation Log

*(To be updated as implementation progresses)*
