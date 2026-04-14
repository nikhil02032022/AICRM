# Group L — Integrations & Document Management
**Status: ✅ COMPLETED** | Completed: 2026-04-14 | Sprint: Sprint 2

## 🎯 Objective
Deliver DigiLocker/Aadhaar integration, ERP/LMS/Alumni bridges, agent commission/comms tools, and advanced document verification, building on Document Management (Sprint 1, Group E) and ERP integration foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| DM-006 | DigiLocker integration | Should Have | ✅ Completed |
| DM-007 | Aadhaar eKYC | Should Have | ✅ Completed |
| EI-008 | Alumni module bridge | Should Have | ✅ Completed |
| EI-010 | LMS enrolment trigger | Should Have | ✅ Completed |
| AG-006 | Agent commission workflow | Should Have | ✅ Completed |
| AG-008 | Agent bulk comms tools | Should Have | ✅ Completed |

## 🧩 Features Breakdown

### Feature: DigiLocker Integration (DM-006) ✅
#### 📌 Description
Allow applicants to submit verified documents via DigiLocker API Setu integration.
#### 👤 User Stories
- As an applicant, I upload documents directly from DigiLocker.
#### ✅ Acceptance Criteria
- Given DigiLocker auth, when document is shared, then it is verified and stored.
#### ⚙️ Backend Design
- Migration: `database/migrations/2026_04_28_000001_create_digilocker_documents_table.php`
- Enum: `app/Enums/CRM/DigiLockerStatus.php` — pending/requested/shared/verified/rejected/failed
- Model: `app/Models/CRM/DigiLockerDocument.php` — HasUuids, SoftDeletes, InstitutionScope
- Repository: `app/Repositories/CRM/Integration/DigiLockerRepositoryInterface.php` + `EloquentDigiLockerRepository.php`
- Service: `app/Services/CRM/Integration/DigiLockerService.php` — initiateRequest dispatches `VerifyDigiLockerDocumentJob` on `crm-integrations` queue
- Event: `app/Events/CRM/DigiLockerVerifiedEvent.php`
- Job: `app/Jobs/CRM/VerifyDigiLockerDocumentJob.php` — tries:3, backoff:60
- FormRequest: `app/Http/Requests/CRM/InitiateDigiLockerRequest.php`
- JsonResource: `app/Http/Resources/CRM/DigiLockerDocumentResource.php`
- Web Controller: `app/Http/Controllers/Web/CRM/DigiLockerWebController.php`
- API Controller: `app/Http/Controllers/Api/CRM/DigiLockerController.php`
#### 🎨 UI/UX
- `resources/views/crm/integrations/digilocker.blade.php` — paginated documents table, initiate-request modal (lead_uuid, document_type, consent_record_id), status badge pills
#### 🔗 Dependencies
- Document Management (E), `crm-integrations` queue
#### 🔐 Security / DPDP
- `consent_record_id` required at every request (DPDP Act 2023)
- Encrypted S3 storage path; no raw document data in logs
- InstitutionScope enforces multi-tenant isolation
#### 🛣️ Routes
- Web: `GET/POST /crm/integrations/digilocker` → `crm.integrations.digilocker.*`
- API: `GET/POST /api/v1/crm/integrations/digilocker`, `GET /api/v1/crm/integrations/digilocker/{uuid}`
#### 🧪 Test Cases
- `tests/Feature/CRM/DigiLockerTest.php` — initiateRequest creates record + dispatches job; markVerified sets status + is_verified; markFailed; institution scope isolation

---

### Feature: Aadhaar eKYC (DM-007) ✅
#### 📌 Description
Aadhaar-based identity verification via API Setu (OTP-based eKYC).
#### 👤 User Stories
- As an applicant, I verify my identity via Aadhaar OTP.
#### ✅ Acceptance Criteria
- Given Aadhaar number, when OTP is verified, then KYC is marked complete.
#### ⚙️ Backend Design
- Migration: `database/migrations/2026_04_28_000002_create_aadhaar_ekyc_logs_table.php`
- Enum: `app/Enums/CRM/AadhaarKycStatus.php` — initiated/otp_sent/verified/failed/expired
- Model: `app/Models/CRM/AadhaarEkycLog.php` — HasUuids, SoftDeletes, InstitutionScope
- Repository: `app/Repositories/CRM/Integration/AadhaarRepositoryInterface.php` + `EloquentAadhaarRepository.php`
- Service: `app/Services/CRM/Integration/AadhaarService.php` — initiate dispatches `ProcessAadhaarKycJob`; verifyOtp sets kyc_complete
- Event: `app/Events/CRM/AadhaarKycCompletedEvent.php`
- Job: `app/Jobs/CRM/ProcessAadhaarKycJob.php` — tries:3, backoff:60
- FormRequests: `InitiateAadhaarKycRequest.php`, `VerifyAadhaarOtpRequest.php`
- JsonResource: `app/Http/Resources/CRM/AadhaarEkycLogResource.php`
- Web Controller: `app/Http/Controllers/Web/CRM/AadhaarEkycWebController.php`
- API Controller: `app/Http/Controllers/Api/CRM/AadhaarController.php`
#### 🎨 UI/UX
- `resources/views/crm/integrations/aadhaar-ekyc.blade.php` — KYC logs table, initiate session form, per-row OTP verify modal (only shown when status = otp_sent), DPDP amber notice banner
#### 🔗 Dependencies
- Document Management (E), `crm-integrations` queue
#### 🔐 Security / DPDP
- **Aadhaar number is NEVER stored** — `aadhaar_ekyc_logs` has no `aadhaar_number` column (UIDAI + DPDP Act 2023 compliance)
- Only `transaction_id`, `otp_reference` stored; test explicitly asserts absence of `aadhaar_number`
- `consent_ip` and `consent_at` recorded at initiation
#### 🛣️ Routes
- Web: `GET/POST /crm/integrations/aadhaar-ekyc`, `PATCH /crm/integrations/aadhaar-ekyc/{uuid}/verify-otp`
- API: `GET/POST /api/v1/crm/integrations/aadhaar`, `GET /api/v1/crm/integrations/aadhaar/{uuid}`
#### 🧪 Test Cases
- `tests/Feature/CRM/AadhaarEkycTest.php` — initiate creates log + dispatches job; `aadhaar_number` column absent (DPDP); verifyOtp sets kyc_complete; markFailed

---

### Feature: Alumni Module Bridge (EI-008) ✅
#### 📌 Description
Auto-populate A2A Alumni module on student graduation, enable alumni referral tracking.
#### 👤 User Stories
- As an admin, I see alumni records and referral impact.
#### ✅ Acceptance Criteria
- Given student graduation, when conversion occurs, then alumni record is created and referrals tracked.
#### ⚙️ Backend Design
- Migration: `database/migrations/2026_04_28_000003_create_alumni_bridge_logs_table.php`
- Enum: `app/Enums/CRM/AlumniBridgeStatus.php` — pending/triggered/success/failed
- Model: `app/Models/CRM/AlumniBridgeLog.php` — HasUuids, SoftDeletes, InstitutionScope
- Repository: `app/Repositories/CRM/Integration/AlumniBridgeRepositoryInterface.php` + `EloquentAlumniBridgeRepository.php`
- Service: `app/Services/CRM/Integration/AlumniBridgeService.php` — trigger dispatches `TriggerAlumniBridgeJob` + fires `AlumniBridgeTriggeredEvent`; markSuccess with erp_alumni_id; incrementReferrals
- Event: `app/Events/CRM/AlumniBridgeTriggeredEvent.php`
- Job: `app/Jobs/CRM/TriggerAlumniBridgeJob.php` — queue: crm-integrations, tries:3, backoff:120
- FormRequest: `app/Http/Requests/CRM/TriggerAlumniBridgeRequest.php`
- JsonResource: `app/Http/Resources/CRM/AlumniBridgeLogResource.php`
- Web Controller: `app/Http/Controllers/Web/CRM/AlumniBridgeWebController.php`
- API Controller: `app/Http/Controllers/Api/CRM/AlumniBridgeController.php`
#### 🎨 UI/UX
- `resources/views/crm/integrations/alumni-bridge.blade.php` — bridge logs table with erp_student_id, erp_alumni_id, referral_code badge, referrals_count counter, status pill, trigger modal
#### 🔗 Dependencies
- ERP integration, Lead conversion, `crm-integrations` queue
#### 🔐 Security / DPDP
- No PII in logs; `payload_summary` field stores non-PII metadata only
- InstitutionScope multi-tenant isolation
#### 🛣️ Routes
- Web: `GET/POST /crm/integrations/alumni-bridge` → `crm.integrations.alumni-bridge.*`
- API: `GET/POST /api/v1/crm/integrations/alumni-bridge`, `GET /api/v1/crm/integrations/alumni-bridge/{uuid}`
#### 🧪 Test Cases
- `tests/Feature/CRM/AlumniBridgeTest.php` — trigger creates log + dispatches job + fires event; markSuccess sets erp_alumni_id; incrementReferrals; markFailed

---

### Feature: LMS Enrolment Trigger (EI-010) ✅
#### 📌 Description
Auto-enrol students in CamPLUS/Moodle LMS upon admission confirmation.
#### 👤 User Stories
- As an admin, I see LMS enrolment status for new students.
#### ✅ Acceptance Criteria
- Given admission, when confirmed, then LMS enrolment is triggered and status tracked.
#### ⚙️ Backend Design
- Migration: `database/migrations/2026_04_28_000004_create_lms_enrolment_logs_table.php`
- Enum: `app/Enums/CRM/LmsEnrolmentStatus.php` — pending/queued/enrolled/failed/retrying
- Model: `app/Models/CRM/LmsEnrolmentLog.php` — HasUuids, SoftDeletes, InstitutionScope
- Repository: `app/Repositories/CRM/Integration/LmsEnrolmentRepositoryInterface.php` + `EloquentLmsEnrolmentRepository.php`
- Service: `app/Services/CRM/Integration/LmsEnrolmentService.php` — trigger dispatches `TriggerLmsEnrolmentJob`; markEnrolled with lms_user_id; incrementAttempts; markFailed
- Event: `app/Events/CRM/LmsEnrolmentTriggeredEvent.php`
- Job: `app/Jobs/CRM/TriggerLmsEnrolmentJob.php` — queue: crm-integrations, tries:3, backoff:120
- FormRequest: `app/Http/Requests/CRM/TriggerLmsEnrolmentRequest.php` — lms_provider validated `in:camplus,moodle`
- JsonResource: `app/Http/Resources/CRM/LmsEnrolmentLogResource.php`
- Web Controller: `app/Http/Controllers/Web/CRM/LmsEnrolmentWebController.php`
- API Controller: `app/Http/Controllers/Api/CRM/LmsEnrolmentController.php`
#### 🎨 UI/UX
- `resources/views/crm/integrations/lms-enrolment.blade.php` — enrolment logs table with provider badge (CamPLUS/Moodle), attempt_count indicator (red if ≥3), status pill, provider filter tabs, trigger modal
#### 🔗 Dependencies
- ERP integration, Application pipeline, `crm-integrations` queue
#### 🔐 Security / DPDP
- No PII in logs; lms_user_id is LMS-generated ID only
- InstitutionScope multi-tenant isolation
#### 🛣️ Routes
- Web: `GET/POST /crm/integrations/lms-enrolment` → `crm.integrations.lms-enrolment.*`
- API: `GET/POST /api/v1/crm/integrations/lms-enrolments`, `GET /api/v1/crm/integrations/lms-enrolments/{uuid}`
#### 🧪 Test Cases
- `tests/Feature/CRM/LmsEnrolmentTest.php` — trigger creates log + dispatches job; markEnrolled sets lms_user_id; markFailed; incrementAttempts

---

### Feature: Agent Commission Workflow (AG-006) ✅
#### 📌 Description
Automate agent commission calculation, approval, and payout workflow.
#### 👤 User Stories
- As an agent, I track commission status; as finance, I approve payouts.
#### ✅ Acceptance Criteria
- Given enrolment, when commission is due, then workflow is triggered and tracked.
#### ⚙️ Backend Design
- Migration: `database/migrations/2026_04_28_000005_create_agent_commissions_table.php`
- Enum: `app/Enums/CRM/CommissionStatus.php` — pending/approved/rejected/paid
- Model: `app/Models/CRM/AgentCommission.php` — HasUuids, SoftDeletes, InstitutionScope
- Repository: `app/Repositories/CRM/Agent/AgentCommissionRepositoryInterface.php` + `EloquentAgentCommissionRepository.php`
- Service: `app/Services/CRM/Agent/AgentCommissionService.php` — create dispatches `ProcessAgentCommissionJob`; approve fires `AgentCommissionApprovedEvent`; reject; markPaid with payout_reference
- Event: `app/Events/CRM/AgentCommissionApprovedEvent.php`
- Job: `app/Jobs/CRM/ProcessAgentCommissionJob.php` — queue: crm-agents, tries:3, backoff:60
- FormRequests: `StoreAgentCommissionRequest.php` (fixed/percentage conditional), `UpdateAgentCommissionRequest.php` (action: approve|reject|pay)
- JsonResource: `app/Http/Resources/CRM/AgentCommissionResource.php`
- Web Controller: `app/Http/Controllers/Web/CRM/AgentCommissionWebController.php`
- API Controller: `app/Http/Controllers/Api/CRM/AgentCommissionController.php`
#### 🎨 UI/UX
- `resources/views/crm/agents/commission.blade.php` — commissions table with status filter tabs, inline Approve/Reject/Pay action forms per row, create commission modal with fixed/percentage toggle (Alpine.js x-model)
#### 🔗 Dependencies
- Agent Management, ERP integration, `crm-agents` queue
#### 🔐 Security / DPDP
- No PII in logs; agent identified by `agent_user_id` FK only
- `approved_by` + `approved_at` recorded for audit trail
#### 🛣️ Routes
- Web: `GET/POST /crm/agents/commissions`, `PATCH /crm/agents/commissions/{uuid}` → `crm.agents.commission.*`
- API: `GET/POST /api/v1/crm/agents/commissions`, `GET/PATCH /api/v1/crm/agents/commissions/{uuid}`
#### 🧪 Test Cases
- `tests/Feature/CRM/AgentCommissionTest.php` — create dispatches job; approve fires event + sets approved_by/at; reject; markPaid with payout_reference

---

### Feature: Agent Bulk Communication Tools (AG-008) ✅
#### 📌 Description
Bulk email/WhatsApp/SMS tools for agent network communication.
#### 👤 User Stories
- As an admin, I send bulk updates to agents.
#### ✅ Acceptance Criteria
- Given a message, when sent, then all agents receive via selected channel.
#### ⚙️ Backend Design
- Migration: `database/migrations/2026_04_28_000006_create_agent_comms_logs_table.php`
- Enum: `app/Enums/CRM/AgentCommsChannel.php` — email/whatsapp/sms
- Model: `app/Models/CRM/AgentCommsLog.php` — HasUuids, SoftDeletes, InstitutionScope
- Repository: `app/Repositories/CRM/Agent/AgentCommsRepositoryInterface.php` + `EloquentAgentCommsRepository.php`
- Service: `app/Services/CRM/Agent/AgentCommsService.php` — send dispatches `SendAgentBulkCommsJob`; recordDelivery fires `AgentBulkCommsSentEvent`
- Event: `app/Events/CRM/AgentBulkCommsSentEvent.php`
- Job: `app/Jobs/CRM/SendAgentBulkCommsJob.php` — queue: crm-agents, tries:2, backoff:60
- FormRequest: `app/Http/Requests/CRM/StoreAgentCommsRequest.php` — channel (email|whatsapp|sms), subject required_if:email, recipient_agent_ids[]
- JsonResource: `app/Http/Resources/CRM/AgentCommsLogResource.php`
- Web Controller: `app/Http/Controllers/Web/CRM/AgentCommsWebController.php`
- API Controller: `app/Http/Controllers/Api/CRM/AgentCommsController.php`
#### 🎨 UI/UX
- `resources/views/crm/agents/comms.blade.php` — comms log table with channel colour badges (blue/green/amber), subject+message truncation, delivered/failed counters, channel filter tabs, compose modal with channel radio + subject (email only) + textarea + comma-separated agent IDs
#### 🔗 Dependencies
- Communication Engine (F), Agent Management, `crm-agents` queue
#### 🔐 Security / DPDP
- `opt_out_respected` flag always set to `true` — enforced in service layer
- Recipient agent IDs stored as JSON array (no personal data); DPDP notice rendered in compose modal
- Opt-out takes effect within 24 hours per DPDP Act 2023 S.6
#### 🛣️ Routes
- Web: `GET/POST /crm/agents/comms` → `crm.agents.comms.*`
- API: `GET/POST /api/v1/crm/agents/comms`, `GET /api/v1/crm/agents/comms/{uuid}`
#### 🧪 Test Cases
- `tests/Feature/CRM/AgentCommsTest.php` — send creates log + dispatches job; recordDelivery fires event + updates counts; opt_out_respected always true; institution scope isolation

---

---

## ?? Implementation Summary

### Files Delivered (Total: 72 files)

| Layer | Count | Files |
|---|---|---|
| Migrations | 6 | 2026_04_28_000001 to _000006 |
| Enums | 6 | DigiLockerStatus, AadhaarKycStatus, AlumniBridgeStatus, LmsEnrolmentStatus, CommissionStatus, AgentCommsChannel |
| Models | 6 | DigiLockerDocument, AadhaarEkycLog, AlumniBridgeLog, LmsEnrolmentLog, AgentCommission, AgentCommsLog |
| Repositories | 12 | 6 interfaces + 6 Eloquent implementations |
| Services | 6 | DigiLockerService, AadhaarService, AlumniBridgeService, LmsEnrolmentService, AgentCommissionService, AgentCommsService |
| Events | 6 | DigiLockerVerifiedEvent, AadhaarKycCompletedEvent, AlumniBridgeTriggeredEvent, LmsEnrolmentTriggeredEvent, AgentCommissionApprovedEvent, AgentBulkCommsSentEvent |
| Jobs | 6 | VerifyDigiLockerDocumentJob, ProcessAadhaarKycJob, TriggerAlumniBridgeJob, TriggerLmsEnrolmentJob, ProcessAgentCommissionJob, SendAgentBulkCommsJob |
| FormRequests | 8 | InitiateDigiLockerRequest, InitiateAadhaarKycRequest, VerifyAadhaarOtpRequest, TriggerAlumniBridgeRequest, TriggerLmsEnrolmentRequest, StoreAgentCommissionRequest, UpdateAgentCommissionRequest, StoreAgentCommsRequest |
| JsonResources | 6 | One per feature |
| Web Controllers | 6 | Under pp/Http/Controllers/Web/CRM/ |
| API Controllers | 6 | Under pp/Http/Controllers/Api/CRM/ |
| Blade Views | 6 | Under 
esources/views/crm/integrations/ and crm/agents/ |
| Feature Tests | 6 | 24 tests total � 4 per feature |
| Service Provider | 1 | CrmIntegrationServiceProvider registered in ootstrap/providers.php |

### Routes Registered

- **Web routes** (21): prefixed crm/integrations/... and crm/agents/..., session auth middleware
- **API routes** (12): prefixed pi/v1/crm/integrations/... and pi/v1/crm/agents/..., Sanctum token auth

### Queue Workers Required

| Queue | Jobs |
|---|---|
| crm-integrations | VerifyDigiLockerDocumentJob, ProcessAadhaarKycJob, TriggerAlumniBridgeJob, TriggerLmsEnrolmentJob |
| crm-agents | ProcessAgentCommissionJob, SendAgentBulkCommsJob |
