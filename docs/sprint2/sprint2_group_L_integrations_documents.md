# Group L — Integrations & Document Management

## 🎯 Objective
Deliver DigiLocker/Aadhaar integration, ERP/LMS/Alumni bridges, agent commission/comms tools, and advanced document verification, building on Document Management (Sprint 1, Group E) and ERP integration foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| DM-006 | DigiLocker integration | Should Have | ⏳ |
| DM-007 | Aadhaar eKYC | Should Have | ⏳ |
| EI-008 | Alumni module bridge | Should Have | ⏳ |
| EI-010 | LMS enrolment trigger | Should Have | ⏳ |
| AG-006 | Agent commission workflow | Should Have | ⏳ |
| AG-008 | Agent bulk comms tools | Should Have | ⏳ |

## 🧩 Features Breakdown

### Feature: DigiLocker Integration (DM-006)
#### 📌 Description
Allow applicants to submit verified documents via DigiLocker API Setu integration.
#### 👤 User Stories
- As an applicant, I upload documents directly from DigiLocker.
#### ✅ Acceptance Criteria
- Given DigiLocker auth, when document is shared, then it is verified and stored.
#### ⚙️ Backend Design
- Services: DigiLockerService
- DB Schema: digilocker_documents
#### 🎨 UI/UX
- digilocker.blade.php (upload, status)
#### 🔗 Dependencies
- Document Management (E)
#### 🔐 Security / DPDP
- API Setu compliance, encrypted storage
#### 🧪 Test Cases
- Upload, verification, error handling

---

### Feature: Aadhaar eKYC (DM-007)
#### 📌 Description
Aadhaar-based identity verification via API Setu (OTP-based eKYC).
#### 👤 User Stories
- As an applicant, I verify my identity via Aadhaar OTP.
#### ✅ Acceptance Criteria
- Given Aadhaar number, when OTP is verified, then KYC is marked complete.
#### ⚙️ Backend Design
- Services: AadhaarService
- DB Schema: aadhaar_ekyc_logs
#### 🎨 UI/UX
- aadhaar-ekyc.blade.php (OTP flow)
#### 🔗 Dependencies
- Document Management (E)
#### 🔐 Security / DPDP
- API Setu compliance, audit log
#### 🧪 Test Cases
- OTP flow, KYC status

---

### Feature: Alumni Module Bridge (EI-008)
#### 📌 Description
Auto-populate A2A Alumni module on student graduation, enable alumni referral tracking.
#### 👤 User Stories
- As an admin, I see alumni records and referral impact.
#### ✅ Acceptance Criteria
- Given student graduation, when conversion occurs, then alumni record is created and referrals tracked.
#### ⚙️ Backend Design
- Services: AlumniBridgeService
- DB Schema: alumni_bridge_logs
#### 🎨 UI/UX
- alumni-bridge.blade.php (referral dashboard)
#### 🔗 Dependencies
- ERP integration, Lead conversion
#### 🔐 Security / DPDP
- No PII in logs, audit log
#### 🧪 Test Cases
- Graduation, referral tracking

---

### Feature: LMS Enrolment Trigger (EI-010)
#### 📌 Description
Auto-enrol students in CamPLUS/Moodle LMS upon admission confirmation.
#### 👤 User Stories
- As an admin, I see LMS enrolment status for new students.
#### ✅ Acceptance Criteria
- Given admission, when confirmed, then LMS enrolment is triggered and status tracked.
#### ⚙️ Backend Design
- Services: LmsEnrolmentService
- DB Schema: lms_enrolment_logs
#### 🎨 UI/UX
- lms-enrolment.blade.php (status, error log)
#### 🔗 Dependencies
- ERP integration, Application pipeline
#### 🔐 Security / DPDP
- No PII in logs, audit log
#### 🧪 Test Cases
- Enrolment trigger, error handling

---

### Feature: Agent Commission Workflow (AG-006)
#### 📌 Description
Automate agent commission calculation, approval, and payout workflow.
#### 👤 User Stories
- As an agent, I track commission status; as finance, I approve payouts.
#### ✅ Acceptance Criteria
- Given enrolment, when commission is due, then workflow is triggered and tracked.
#### ⚙️ Backend Design
- Services: AgentCommissionService
- DB Schema: agent_commissions
#### 🎨 UI/UX
- agent-commission.blade.php (status, approval)
#### 🔗 Dependencies
- Agent Management, ERP integration
#### 🔐 Security / DPDP
- Audit log, no PII in logs
#### 🧪 Test Cases
- Commission calculation, approval

---

### Feature: Agent Bulk Communication Tools (AG-008)
#### 📌 Description
Bulk email/WhatsApp/SMS tools for agent network communication.
#### 👤 User Stories
- As an admin, I send bulk updates to agents.
#### ✅ Acceptance Criteria
- Given a message, when sent, then all agents receive via selected channel.
#### ⚙️ Backend Design
- Services: AgentCommsService
- DB Schema: agent_comms_logs
#### 🎨 UI/UX
- agent-comms.blade.php (compose, history)
#### 🔗 Dependencies
- Communication Engine (F), Agent Management
#### 🔐 Security / DPDP
- Opt-out, audit log
#### 🧪 Test Cases
- Bulk send, opt-out

---
