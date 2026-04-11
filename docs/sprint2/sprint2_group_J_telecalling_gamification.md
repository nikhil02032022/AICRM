# Group J — Telecalling & Gamification

## 🎯 Objective
Deliver power/auto-dialler, call scripts, supervisor call monitoring, counsellor gamification, business card OCR, mobile offline mode, and biometric authentication, building on Telephony/IVR (Sprint 1, Group F) and Counselling foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| TC-001 | Power/auto-dialler | Should Have | ⏳ |
| TC-002 | Call scripts with branching | Should Have | ⏳ |
| TC-005 | Supervisor call monitoring | Should Have | ⏳ |
| EC-010 | Counsellor performance gamification | Should Have | ⏳ |
| MB-004 | Business card scanner (OCR) | Should Have | ⏳ |
| MB-006 | Mobile offline mode | Should Have | ⏳ |
| MB-007 | Biometric authentication | Should Have | ⏳ |

## 🧩 Features Breakdown

### Feature: Power/Auto-Dialler (TC-001)
#### 📌 Description
Automated dialler for outbound calling campaigns, integrated with CRM lead lists.
#### 👤 User Stories
- As a counsellor, I can auto-dial leads from a campaign list.
#### ✅ Acceptance Criteria
- Given a campaign, when dialler is started, then calls are auto-placed and logged.
#### ⚙️ Backend Design
- Controllers: DiallerController
- Services: DiallerService
- Jobs: DiallerJob (queue: crm-telecalling)
- Events: CallPlacedEvent, CallCompletedEvent
- DB Schema: dialler_sessions, dialler_logs
#### 🎨 UI/UX
- dialler.blade.php (call queue, status)
#### 🔗 Dependencies
- Telephony/IVR (F), Lead foundation (A)
#### 🔐 Security / DPDP
- Call consent, no PII in logs
#### 🧪 Test Cases
- Dialler flow, call logging, DPDP consent

---

### Feature: Call Scripts with Branching (TC-002)
#### 📌 Description
Configurable call scripts with dynamic branching based on responses.
#### 👤 User Stories
- As a counsellor, I follow guided scripts during calls.
#### ✅ Acceptance Criteria
- Given a call, when script is active, then prompts and branches are shown per responses.
#### ⚙️ Backend Design
- Controllers: CallScriptController
- Models: CallScript
- DB Schema: call_scripts, call_script_steps
#### 🎨 UI/UX
- call-script.blade.php (script flow)
#### 🔗 Dependencies
- Telephony/IVR (F)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Script creation, branching logic

---

### Feature: Supervisor Call Monitoring (TC-005)
#### 📌 Description
Supervisors can listen, whisper, or barge-in on live calls for QA/training.
#### 👤 User Stories
- As a supervisor, I monitor and coach live calls.
#### ✅ Acceptance Criteria
- Given a live call, when supervisor joins, then listen/whisper/barge-in is available.
#### ⚙️ Backend Design
- Services: CallMonitorService
- DB Schema: call_monitor_logs
#### 🎨 UI/UX
- call-monitor.blade.php (monitor panel)
#### 🔗 Dependencies
- Telephony/IVR (F)
#### 🔐 Security / DPDP
- Consent, audit log
#### 🧪 Test Cases
- Monitor, whisper, barge-in

---

### Feature: Counsellor Performance Gamification (EC-010)
#### 📌 Description
Gamified dashboard for counsellor KPIs, leaderboards, badges, and rewards.
#### 👤 User Stories
- As a counsellor, I see my rank and earn rewards for performance.
#### ✅ Acceptance Criteria
- Given activity, when KPIs are met, then badges/leaderboard update.
#### ⚙️ Backend Design
- Services: GamificationService
- DB Schema: gamification_scores, badges, leaderboards
#### 🎨 UI/UX
- gamification-dashboard.blade.php (leaderboard, badges)
#### 🔗 Dependencies
- Lead activity, Analytics (K)
#### 🔐 Security / DPDP
- No PII in logs
#### 🧪 Test Cases
- Score calculation, badge assignment

---

### Feature: Business Card Scanner (OCR) (MB-004)
#### 📌 Description
Mobile OCR to scan business cards and auto-create leads.
#### 👤 User Stories
- As a counsellor, I scan a card and a lead is created.
#### ✅ Acceptance Criteria
- Given a card scan, when OCR is successful, then lead is created with extracted data.
#### ⚙️ Backend Design
- Services: OcrService
- DB Schema: ocr_uploads
#### 🎨 UI/UX
- ocr-upload.blade.php (mobile UI)
#### 🔗 Dependencies
- Mobile app, Lead foundation (A)
#### 🔐 Security / DPDP
- Consent, no PII in logs
#### 🧪 Test Cases
- OCR accuracy, lead creation

---

### Feature: Mobile Offline Mode (MB-006)
#### 📌 Description
Allow mobile users to view, add notes, and scan cards offline, with sync on reconnect.
#### 👤 User Stories
- As a counsellor, I work offline and sync later.
#### ✅ Acceptance Criteria
- Given offline mode, when reconnected, then data syncs to CRM.
#### ⚙️ Backend Design
- Mobile app update, sync logic
#### 🎨 UI/UX
- Mobile UI (offline indicators)
#### 🔗 Dependencies
- Mobile app
#### 🔐 Security / DPDP
- Local encryption, DPDP for sync
#### 🧪 Test Cases
- Offline actions, sync

---

### Feature: Biometric Authentication (MB-007)
#### 📌 Description
Enable fingerprint/face unlock for mobile app access.
#### 👤 User Stories
- As a counsellor, I log in with biometrics.
#### ✅ Acceptance Criteria
- Given biometric setup, when enabled, then login uses fingerprint/face.
#### ⚙️ Backend Design
- Mobile app update, auth logic
#### 🎨 UI/UX
- Mobile UI (biometric prompt)
#### 🔗 Dependencies
- Mobile app
#### 🔐 Security / DPDP
- Biometric data never leaves device
#### 🧪 Test Cases
- Biometric login, fallback

---
