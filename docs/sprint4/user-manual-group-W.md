# User Manual — Group W: System Administration, Compliance and Alumni Pipeline

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Sprint:** 4 | **Group:** W
**Last Updated:** 2026-04-23

---

## Overview

This manual covers the System Administration, Compliance, and Alumni Pipeline modules delivered in Sprint 4 Group W. These modules are primarily used by Institution Admins and Super Admins. Certain read-only views are available to Admissions Directors. Counsellors do not have access to these areas.

---

## Role-Based Access Summary

| Feature | Super Admin | Institution Admin | Admissions Director | Counsellor |
|---|---|---|---|---|
| System Administration | Full | Full | Read-only | None |
| Compliance | Full | Full | Read-only | None |
| Alumni Pipeline | Full | Full | View only | None |

---

## System Administration

### Institution Management (SA-001)

**Who can access:** Super Admin (full), Institution Admin (full), Admissions Director (read-only)

**URL:** `/crm/admin/institution`

The Institution Management page displays your organisation's core profile, including name, logo, primary colour, timezone, locale, and contact details.

#### How to view the institution profile

1. Navigate to **Admin → Institution** in the sidebar.
2. The profile page displays all current settings in a read-only summary view.

#### How to edit the institution profile

> Only users with the **Institution Admin** or **Super Admin** role can make changes.

1. Navigate to **Admin → Institution**.
2. Click **Edit Profile**.
3. Update the required fields. Supported fields include:
   - **Institution Name** — displayed throughout the CRM and in outbound communications.
   - **Logo** — uploaded logo appears in the header and on exported documents.
   - **Primary Colour** — hex colour code used for branding.
   - **Timezone** — controls all date/time display and scheduling across the institution.
   - **Locale** — sets the default language and number format.
   - **Business Hours** — JSON-configurable operating hours used for SLA calculations.
4. Click **Save Changes**.

A success notification is shown. All changes are recorded in the audit trail with the previous and updated values.

---

### Campus Management (SA-002)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/admin/campuses`

Each institution can have one or more campuses. Leads, programmes, and counsellors can be associated with specific campuses.

#### How to add a campus

1. Navigate to **Admin → Campuses**.
2. Click **Add Campus**.
3. Fill in the required fields:
   - **Name** — full campus name (e.g. "Mumbai Campus").
   - **Code** — short identifier used in reports and exports (e.g. "MUM").
   - **City** — city where the campus is located.
4. Click **Save**. The new campus appears in the campus list with status **Active**.

#### How to edit a campus

1. Navigate to **Admin → Campuses**.
2. Click the **Edit** icon next to the campus.
3. Update the required fields and click **Save**.

#### How to deactivate a campus

1. Navigate to **Admin → Campuses**.
2. Click **Deactivate** next to the campus record.
3. Confirm the action when prompted.

Deactivated campuses are hidden from lead and programme selection forms but remain in the database and are preserved in historical records.

---

### Academic Years (SA-003)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/admin/academic-years`

Academic years define the admission cycles for your institution. Each year tracks seat counts and pipeline stage metrics.

#### How to create a new academic year

1. Navigate to **Admin → Academic Years**.
2. Click **Create Academic Year**.
3. Enter the **Label** (e.g. "2026-27"), **Start Date**, and **End Date**.
4. Click **Save**. The year is created with status **Closed**.

#### How to set an academic year as active

1. Navigate to **Admin → Academic Years**.
2. Click **Set Active** next to the year you wish to activate.

Only one academic year can be active at a time. Setting a new year active automatically closes the previously active year.

#### How to roll over to a new academic year

Rollover copies seat allocation data from the current year to a new academic year and resets all pipeline stage counters (e.g. leads received, applications submitted, enrolled) to zero, allowing a clean start for the new admissions cycle.

Run the following Artisan command:

```bash
php artisan crm:rollover-academic-year {institution_id} {new_year_label}
```

**Example:**

```bash
php artisan crm:rollover-academic-year 1 "2027-28"
```

After the rollover:
- A new academic year record is created with `rolled_over_from_id` pointing to the source year.
- Seat counts are copied from the source year.
- Pipeline stage counters on the new year are reset to zero.
- The new year starts in **Closed** status. Set it to Active when the cycle begins.

---

### Audit Trail (SA-004)

**Who can access:** Super Admin (full), Institution Admin (full), Admissions Director (read-only)

**URL:** `/crm/admin/audit-trail`

The audit trail provides a complete, tamper-proof record of all changes made to CRM data within your institution.

> The audit trail is **read-only**. No entries can be edited or deleted by any user.

#### What is recorded

Every create, update, and delete action on a CRM model is logged with:
- **User** — the account that made the change.
- **Entity Type** — the model affected (e.g. Lead, Application, Campus).
- **Entity ID** — the specific record that was changed.
- **Event** — created, updated, or deleted.
- **Changed Fields** — a field-by-field comparison of old and new values.
- **IP Address** — the IP from which the change was made.
- **Timestamp** — the exact date and time of the change.

> PII values (mobile numbers, email addresses) are masked in the Changed Fields display to prevent exposure of sensitive data in the audit log UI.

#### How to filter the audit trail

1. Navigate to **Admin → Audit Trail**.
2. Use the filter bar to narrow results by:
   - **Entity Type** — select a model from the dropdown (e.g. Lead, Application).
   - **User** — select a specific user account.
   - **Date Range** — enter a from and to date.
3. Click **Apply Filters**. The table updates to show matching entries.

---

### Data Import / Export (SA-005)

**Who can access:** Super Admin (full), Institution Admin (full)

#### How to import leads from CSV or XLSX

1. Navigate to **Admin → Data Import**.
2. Click **Choose File** and select your CSV or XLSX file.
3. Select the **Entity Type** (e.g. Leads, Applications, Contacts).
4. Review the **Column Mapping** panel to confirm that your file's columns are matched to the correct CRM fields. Adjust any auto-mapped columns if needed.
5. Click **Import**.

**Expected column headers for leads:**

| Column | Required |
|---|---|
| `first_name` | Yes |
| `last_name` | Yes |
| `email` | Yes |
| `mobile` | Yes |
| `source` | No |
| `programme_interest` | No |
| `campus_code` | No |

**Error handling:**

- If required headers are missing, the import is rejected before any rows are processed. A clear error lists the missing columns.
- If individual rows contain validation errors (e.g. missing required field, invalid email), those rows are skipped and listed in a downloadable **Error Report**. Valid rows are imported atomically.

#### How to export leads or applications

1. Navigate to **Admin → Data Export**.
2. Select the **Entity Type** (Leads, Applications, or Contacts).
3. Select the **File Format** (CSV or XLSX).
4. Optionally apply filters (date range, status, campus).
5. Click **Export**. The file downloads immediately.

Exported data is scoped to your institution. You will never receive data from another institution.

---

### System Configuration (SA-006)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/admin/system-config`

The System Configuration page provides a tabbed settings interface for institution-level preferences.

> Changes take effect immediately after saving. No restart or cache clear is required.

#### Available configuration tabs

| Tab | Settings |
|---|---|
| **General** | Institution name, timezone, locale, date format |
| **Branding** | Logo upload, primary colour, email header |
| **Business Hours** | Operating hours per day of week; used for SLA timers |
| **Notifications** | Default notification channels, alert thresholds |
| **Storage** | File storage driver (enforced to India region in production) |

#### How to change a setting

1. Navigate to **Admin → System Configuration**.
2. Select the relevant tab.
3. Update the field value.
4. Click **Save** (each tab saves independently).

All configuration changes are written to the `system_configs` table and audited.

---

### Custom Fields (SA-008)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/admin/custom-fields`

Custom fields allow you to extend the standard Lead, Application, and Student forms with institution-specific data points.

#### How to create a custom field

1. Navigate to **Admin → Custom Fields**.
2. Click **Add Field**.
3. Fill in the following:
   - **Entity Type** — the form this field will appear on (Lead, Application, or Student).
   - **Label** — the display name shown to users (e.g. "Preferred Intake Season").
   - **Field Key** — auto-generated lowercase identifier (e.g. `preferred_intake_season`).
   - **Field Type** — select from: Text, Textarea, Number, Date, Select, Multi-Select, Checkbox, File.
   - **Options** — for Select and Multi-Select types, enter the available choices (one per line).
   - **Required** — toggle on if the field must be filled in on form submission.
4. Click **Save**.

The field immediately appears on the relevant form for users in your institution.

#### How to reorder custom fields

1. Navigate to **Admin → Custom Fields**.
2. Use the drag handle (the six-dot icon) on each field row to drag it to the desired position.
3. Click **Save Order**.

The new order is saved and reflected immediately on the lead/application forms.

---

### Notification Templates (SA-009)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/admin/notification-templates`

Notification templates control the content of automated emails, SMS messages, and WhatsApp notifications sent by the CRM.

#### How to create a notification template

1. Navigate to **Admin → Notification Templates**.
2. Click **Create Template**.
3. Configure the template:
   - **Channel** — Email, SMS, or WhatsApp.
   - **Name** — an internal identifier (e.g. "Application Received — Email").
   - **Subject** — (Email only) the email subject line.
   - **Body** — the message content. Use merge tags to personalise the message.
4. Click **Save**.

#### Available merge tags

Merge tags are placeholders that are replaced with actual data when the notification is sent.

| Merge Tag | Replaced With |
|---|---|
| `{{lead.name}}` | Lead's full name |
| `{{lead.email}}` | Lead's email address |
| `{{lead.mobile}}` | Lead's mobile number |
| `{{application.programme}}` | Programme name on the application |
| `{{institution.name}}` | Institution name |
| `{{counsellor.name}}` | Assigned counsellor's name |

#### How to preview a template

1. Open the template you want to preview.
2. Click **Preview**.
3. A rendered version of the template appears with placeholder values substituted for merge tags.

Review the preview before activating the template to ensure the content and formatting are correct.

---

### Backups (SA-012)

**Who can access:** Super Admin only

**URL:** `/crm/admin/backups`

The CRM automatically backs up the database daily using `spatie/laravel-backup`.

#### Automated backups

- Automated backups run every day at **03:00** (server time).
- Backup files are stored in the configured storage location (India region in production).
- Completed backups are listed on the Backups page with filename, size, and creation time.

#### How to run a manual backup

1. Navigate to **Admin → Backups**.
2. Click **Run Now**.
3. A backup job is dispatched to the queue. The page refreshes once the job completes and the new backup appears in the list.

#### How to download a completed backup

1. Navigate to **Admin → Backups**.
2. Locate the backup in the list.
3. Click **Download** next to the entry.

> Store downloaded backup files securely. They contain the full database including personal data.

---

## Compliance Controls (DPDP Act 2023 / TRAI)

This section covers the tools provided to help your institution comply with the Digital Personal Data Protection Act 2023 and TRAI / DoT regulations on communications.

---

### Consent Records (CR-001 / CR-002)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/compliance/consent`

When a lead is created through the CRM lead form, a consent checkbox is presented. If the lead checks the box, a `ConsentRecord` is automatically created and attached to the lead.

#### What is stored in each consent record

| Field | Description |
|---|---|
| Consent Type | The purpose of consent (e.g. Data Processing, Marketing Communication) |
| Form Version | The version of the form at the time consent was given |
| IP Address | The IP address from which the form was submitted |
| User Agent | The browser or device string |
| Consented At | ISO 8601 timestamp of consent |
| Revoked At | Populated if consent is later withdrawn |

#### How to view a lead's consent history

1. Open a lead record.
2. Scroll to the **Consent History** tab.
3. All consent records linked to the lead are listed, including any revocations.

---

### Opt-Out Management (CR-003)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/compliance/opt-out`

Leads can request to opt out of communications (email, SMS, WhatsApp, or all channels) through the lead portal or by contacting your institution.

#### How opt-outs are processed

- When a lead submits an opt-out request, an `OptOutLog` record is created with `requested_at` populated.
- The `ProcessOptOutJob` runs every **15 minutes** via the scheduler.
- Pending opt-out requests are processed within a **24-hour SLA**.
- Once processed, `processed_at` is set and the lead's communication preferences are updated to block the relevant channel(s).

#### How to manually process a pending opt-out

1. Navigate to **Compliance → Opt-Out Requests**.
2. Pending requests are listed with the lead name, channel, and request time.
3. Click **Process Now** next to a request to process it immediately.

---

### Data Access Requests (CR-004)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/compliance/data-access`

Under the DPDP Act 2023, leads and applicants have the right to request a copy of their stored personal data.

#### How to submit a data access request

1. Navigate to **Compliance → Data Access Requests**.
2. Click **New Request**.
3. Select the lead from the search field.
4. Choose the **Delivery Method**: on-screen view or email.
5. Click **Submit**.

The `DataAccessService` compiles a data package containing the lead's personal information, application records, communication history, and consent records. The compiled data is presented on screen and/or sent to the lead's registered email address.

#### What is included in the data package

- Personal details (name, email, mobile, address)
- Application history
- Communication logs
- Consent and opt-out records
- Custom field values

---

### PII Erasure (CR-005)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/compliance/erasure`

Under the DPDP Act 2023 right to erasure, verified requests result in the anonymisation of the lead's personally identifiable information.

#### How erasure works

- An erasure request is submitted and a `PiiErasureRequest` record is created.
- `scheduled_erasure_at` is set to **30 days** from the request date, providing a review window.
- The `ErasePersonalDataJob` runs daily at **02:30** (server time) and processes requests whose scheduled erasure date has passed.
- PII fields (name, email, mobile, address, and other personal data fields) are replaced with `[ERASED]`. Non-required fields are set to null.
- The lead record is **not deleted** — aggregate counts, application history, and IDs are preserved to maintain referential integrity in reports.
- The erasure event is written to the audit trail.

#### How to submit an erasure request

1. Navigate to **Compliance → Erasure Requests**.
2. Click **New Request**.
3. Select the lead and confirm the request.
4. The record is created with status **Pending** and a scheduled erasure date 30 days in the future.

#### Manual erasure (admin only)

Super Admins can trigger erasure immediately for an individual lead using the Artisan command:

```bash
php artisan crm:gdpr:erase {lead_id}
```

---

### Data Residency (CR-006)

**Who can access:** Informational — enforced automatically by middleware

All personal data of Indian residents must be stored on India-hosted infrastructure. The CRM enforces this through the `DataResidencyCheck` middleware.

#### Enforcement behaviour

| Environment | Behaviour |
|---|---|
| `production` | File uploads to non-India storage regions are **blocked** with an error message |
| `staging` | File uploads to non-India storage regions are **blocked** |
| `local` / `testing` | A warning is written to the application log; upload proceeds unblocked |

If you receive a data residency error in production, contact your system administrator to verify that the storage driver is configured to use the `ap-south-1` (Mumbai) region.

---

### DLT Template Compliance (CR-008)

**Who can access:** Informational — validated automatically on SMS dispatch

TRAI requires that all commercial SMS messages sent in India use templates registered with the Distributed Ledger Technology (DLT) portal.

#### How DLT validation works

- Before an SMS is dispatched, the `DltTemplateValidatorService` checks whether the notification template is registered in the DLT registry.
- If the template is **not registered**, a warning is written to the application log.
- In v1, this is **advisory only** — the SMS is still sent. A future release will enforce hard blocking.

#### What to do if you receive a DLT warning

1. Log in to your telecom provider's DLT portal.
2. Register the SMS template content.
3. Update the notification template in the CRM with the DLT-registered template ID.

---

### Data Processing Agreement (CR-009)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/compliance/dpa`

The Data Processing Agreement (DPA) sets out the terms under which MEETCS processes personal data on behalf of your institution as Data Fiduciary under the DPDP Act 2023.

#### How to view and download the DPA

1. Navigate to **Compliance → Data Processing Agreement**.
2. The DPA page displays a summary of the agreement terms.
3. Click **Download PDF** to download the signed DPA document.

> Only Institution Admins and Super Admins can access and download the DPA.

---

### Security Incidents and Breach Notifications (CR-010)

**Who can access:** Super Admin (full), Institution Admin (full)

**URL:** `/crm/compliance/incidents`

In the event of a data breach or security incident, the CRM provides a structured workflow to report the incident and dispatch breach notifications to institution admins within the 72-hour window required by the DPDP Act 2023.

#### How to report a security incident

1. Navigate to **Compliance → Security Incidents**.
2. Click **Report Incident**.
3. Fill in the required fields:
   - **Incident Type** — select the category (e.g. Unauthorised Access, Data Leak, System Breach).
   - **Description** — provide a detailed description of the incident.
   - **Detection Time** — the date and time the incident was first detected.
4. Click **Submit**.

After submission:
- A `SecurityIncident` record is created with status **Reported**.
- The `BreachNotificationJob` is dispatched.
- Breach notification emails are sent to all institution admins and system administrators.
- `notified_at` is populated on the incident record once emails are sent.
- The breach notification targets delivery within **72 hours** of the recorded `detected_at` time.

#### Tracking an incident

1. Navigate to **Compliance → Security Incidents**.
2. Click the incident to view its detail page.
3. Update the status as the investigation progresses: **Reported → Investigating → Notified → Resolved**.
4. Attach documentation (investigation notes, remediation steps) using the documentation field.

---

## Alumni Pipeline (AL-001)

**Who can access:** Super Admin (full), Institution Admin (full), Admissions Director (view only)

**URL:** `/crm/alumni`

> Note: The Alumni Pipeline (AL-001) is a lead-to-alumni bridge that auto-populates the alumni pipeline when a student enrols. It is separate from the ERP Alumni Bridge (EI-008), which handles synchronisation with external ERP systems.

### How alumni records are created automatically

When an application's status is transitioned to **ENROLLED**, the `GraduationObserver` on the `Application` model fires automatically and calls `AlumniPipelineService::enqueue()`. This creates an `AlumniPipeline` record linked to the lead and the application.

No manual action is required from the admin. The process is fully automated on status change.

### Alumni pipeline statuses

| Status | Meaning |
|---|---|
| **Pending** | Alumni record created; awaiting eligibility review |
| **Eligible** | Record reviewed and confirmed eligible for alumni engagement |
| **Synced** | Record synchronised with alumni systems or ERP (via EI-008 when available) |

### How to view and manage the alumni pipeline

1. Navigate to **Alumni → Pipeline** in the sidebar.
2. The pipeline table lists all alumni records with their current status, programme, graduation date, and linked lead.
3. To progress a record:
   - Click the record to open the detail view.
   - Click **Mark Eligible** to move from Pending to Eligible.
   - Click **Mark Synced** to move from Eligible to Synced once the record has been shared with downstream systems.

---

## Frequently Asked Questions

**Q: Can I undo a PII erasure once it has run?**
No. Erasure is irreversible by design. If you need to verify an erasure, check the audit trail for the erasure event, which records which fields were anonymised and when.

**Q: What happens to reports and analytics when a lead is erased?**
Aggregate counts, stage counters, and application history are preserved. Only PII text fields are replaced with `[ERASED]`. Reports and dashboards continue to reflect accurate volume metrics.

**Q: Can two campuses share the same campus code?**
No. Campus codes must be unique within an institution.

**Q: Will custom fields created for one institution appear for another institution's users?**
No. Custom field definitions are scoped to the institution that created them. Other institutions see only their own custom fields.

**Q: How do I know if my SMS templates are DLT-registered?**
Check the application log for DLT warning entries (tagged `dlt.unregistered`). You can also review your templates in the DLT portal of your telecom provider.
