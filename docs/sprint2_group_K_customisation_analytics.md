# Group K — Customisation & Advanced Analytics

## 🎯 Objective
Deliver custom fields, custom report builder, scheduled report delivery, workflow template library, and system health dashboard, building on Analytics (Sprint 1, Group D/F) and Admin foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| EC-005 | Custom fields per institution | Must Have | ⏳ |
| AR-018 | Custom report builder | Should Have | ⏳ |
| AR-020 | Scheduled report delivery | Should Have | ⏳ |
| SA-007 | Workflow/automation template library | Should Have | ⏳ |
| SA-011 | System health monitoring dashboard | Should Have | ⏳ |

## 🧩 Features Breakdown

### Feature: Custom Fields per Institution (EC-005)
#### 📌 Description
Admins can define custom fields for leads/applications, visible in forms, reports, and exports.
#### 👤 User Stories
- As an admin, I add custom fields for my institution.
#### ✅ Acceptance Criteria
- Given a new field, when added, then it appears in forms and reports.
#### ⚙️ Backend Design
- Controllers: CustomFieldController
- Models: CustomField
- DB Schema: custom_fields, custom_field_values
#### 🎨 UI/UX
- custom-field.blade.php (admin UI, field config)
#### 🔗 Dependencies
- Lead foundation (A), Application forms
#### 🔐 Security / DPDP
- Field-level RBAC, audit log
#### 🧪 Test Cases
- Add, edit, delete, RBAC

---

### Feature: Custom Report Builder (AR-018)
#### 📌 Description
Drag-and-drop report builder for custom analytics, field selection, filters, grouping, export.
#### 👤 User Stories
- As a manager, I build and export custom reports.
#### ✅ Acceptance Criteria
- Given report config, when run, then results are shown/exported.
#### ⚙️ Backend Design
- Controllers: CustomReportController
- Models: CustomReport
- DB Schema: custom_reports, report_filters, report_exports
#### 🎨 UI/UX
- custom-report.blade.php (builder, results)
#### 🔗 Dependencies
- Analytics (D/F)
#### 🔐 Security / DPDP
- Export audit log, DPDP for exports
#### 🧪 Test Cases
- Build, run, export, audit

---

### Feature: Scheduled Report Delivery (AR-020)
#### 📌 Description
Schedule reports for auto-generation and email delivery to users.
#### 👤 User Stories
- As a manager, I schedule weekly reports to my inbox.
#### ✅ Acceptance Criteria
- Given a schedule, when due, then report is generated and sent.
#### ⚙️ Backend Design
- Services: ReportScheduler
- Jobs: ReportDeliveryJob (queue: crm-analytics)
- DB Schema: report_schedules, report_deliveries
#### 🎨 UI/UX
- report-scheduler.blade.php (schedule UI)
#### 🔗 Dependencies
- Custom reports, Notification engine
#### 🔐 Security / DPDP
- Email audit log, DPDP for attachments
#### 🧪 Test Cases
- Schedule, delivery, audit

---

### Feature: Workflow/Automation Template Library (SA-007)
#### 📌 Description
Pre-built workflow templates for common automation scenarios, importable into institution config.
#### 👤 User Stories
- As an admin, I import and customise workflow templates.
#### ✅ Acceptance Criteria
- Given a template, when imported, then it is editable and assignable.
#### ⚙️ Backend Design
- Models: WorkflowTemplate
- DB Schema: workflow_templates
#### 🎨 UI/UX
- workflow-template.blade.php (template gallery)
#### 🔗 Dependencies
- Marketing Automation (H)
#### 🔐 Security / DPDP
- Audit log
#### 🧪 Test Cases
- Import, edit, assign

---

### Feature: System Health Monitoring Dashboard (SA-011)
#### 📌 Description
Admin dashboard for system health: API status, queue depths, error logs, uptime.
#### 👤 User Stories
- As an admin, I monitor system health in real time.
#### ✅ Acceptance Criteria
- Given dashboard, when viewed, then live metrics are shown.
#### ⚙️ Backend Design
- Services: SystemHealthService
- DB Schema: system_health_logs
#### 🎨 UI/UX
- system-health.blade.php (metrics, alerts)
#### 🔗 Dependencies
- Queue system, error logging
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Metrics, alerting

---
