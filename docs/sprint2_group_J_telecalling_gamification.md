# Group J — Telecalling & Gamification

## 🎯 Objective
Deliver power/auto-dialler, call scripts, supervisor call monitoring, counsellor gamification, business card OCR, mobile offline mode, and biometric authentication, building on Telephony/IVR (Sprint 1, Group F) and Counselling foundation.

## 🔗 BRD Coverage
| Req ID | Feature | Priority | Status |
|--------|---------|----------|--------|
| TC-001 | Power/auto-dialler | Should Have | ⏳ |
| TC-002 | Call scripts with branching | Should Have | ⏳ |
| TC-005 | Supervisor call monitoring | Should Have | ⏳ |
| EC-010 | Counsellor performance gamification | Should Have | ✅ |
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


## ? Implementation Summary � EC-010 Counsellor Performance Gamification

### Completed: 2026-04-13 18:37

**BRD Requirement:** CRM-EC-010 � Counsellor performance scoring (leads handled, conversion rate, response time, student satisfaction) shall be tracked and gamified with leaderboards, badges, and rewards.

### ?? Artifacts Created

#### Database Layer
- **Migration:** `2026_04_13_125857_create_gamification_tables.php`
  - Tables: `crm_badges`, `crm_gamification_scores`, `crm_leaderboards`, `crm_counsellor_badges`
  - Full multi-tenant scoping (`institution_id`, `campus_id`)
  - Tracks: leads handled/converted, conversion rate, response time, satisfaction score, calls, emails, meetings, applications
  - Period types: daily, weekly, monthly, quarterly, yearly
  - Streak tracking and rank change trending

#### Enums
- `BadgeCategory` � performance | milestone | consistency | excellence | special
- `PeriodType` � daily | weekly | monthly | quarterly | yearly
- `LeaderboardTrend` � up | down | stable

#### Models
- `Badge` � Badge definition with criteria JSON and unlock thresholds
- `GamificationScore` � Counsellor performance metrics per period
- `Leaderboard` � Ranking table with trend indicators
- `CounsellorBadge` � Earned badges pivot model

#### Repository
- `GamificationRepository` � Data access layer
  - Score CRUD operations
  - Leaderboard ranking calculation and updates
  - Badge checking and awarding logic
  - Period date calculations

#### Service Layer
- `GamificationService` � Business logic
  - `recordLeadHandled()` � +5 points
  - `recordLeadConversion()` � +50 points
  - `recordCallMade()` � +2 points
  - `recordEmailSent()` � +1 point
  - `recordMeetingScheduled()` � +10 points
  - `recordApplicationSubmitted()` � +25 points
  - `updateResponseTime()` � Bonus +10 points for <15min response
  - `updateSatisfactionScore()` � Bonus +20 points for 4.5+ rating
  - Automatic badge checking and awarding
  - Streak day tracking and reset logic
  - Top performer and leaderboard queries

#### Events
- `ScoreUpdatedEvent` � Fired on any score change
- `BadgeEarnedEvent` � Fired when counsellor earns a badge
- `LeaderboardUpdatedEvent` � Fired after leaderboard recalculation

#### Jobs
- `UpdateLeaderboardJob` � Calculate rankings for a single institution/campus/period
- `UpdateAllLeaderboardsJob` � Scheduled daily job to refresh all leaderboards

#### Web Controller
- `GamificationController@index` � Main dashboard view
  - Displays counsellor's current score, rank, badges
  - Period selector (daily/weekly/monthly/quarterly/yearly)
  - Leaderboard component with live rankings
  - KPI summary sidebar

#### Views (Blade + Tailwind CSS + Alpine.js)
- `resources/views/crm/gamification/index.blade.php`
  - Clean Professional / Minimal SaaS style (ui-ux-pro-max skill applied)
  - Gradient stat cards for rank, points, conversion rate, streak
  - Responsive grid layout (mobile-first)
  - Badge showcase with tooltips
  - Performance metrics sidebar
  - WCAG AA accessible (contrast 4.5:1, focus states, aria-labels)

#### Livewire Components
- `Leaderboard` component (`app/Livewire/CRM/Gamification/Leaderboard.php`)
  - `livewire.crm.gamification.leaderboard` view
  - Real-time leaderboard table
  - Medal icons for top 3 (??????)
  - Trend indicators (???)
  - Highlighted row for current user

#### Seeders
- `BadgeSeeder` � 13 default badges:
  - **Performance:** First Conversion (100pts), Top Converter (500pts), Conversion Master (2000pts)
  - **Milestone:** Century Club (300pts), Call Champion (200pts)
  - **Consistency:** Week Warrior (250pts), Month Maven (1000pts)
  - **Excellence:** High Rate Achiever (750pts), Speed Demon (400pts), 5-Star Counsellor (600pts)
  - **Special:** Email Expert (150pts), Meeting Maestro (300pts), Application Ace (400pts)

#### Routes
- **Web:** `GET /crm/gamification` ? `GamificationController@index`
  - Middleware: `auth`, `can:crm.leads.view`
  - Named route: `crm.gamification.index`

### ?? Integration Points

The gamification system is designed to be triggered by existing CRM events:
- **Lead Service** should call `GamificationService::recordLeadHandled()` on lead assignment
- **Lead Status Change** should call `GamificationService::recordLeadConversion()` on `LeadStatus::CONVERTED`
- **Call Service** should call `GamificationService::recordCallMade()` on call completion
- **Email Service** should call `GamificationService::recordEmailSent()` on send
- **Meeting Service** should call `GamificationService::recordMeetingScheduled()` on booking
- **Application Service** should call `GamificationService::recordApplicationSubmitted()` on submit
- **Response Time** tracked via `FirstResponseListener` ? `GamificationService::updateResponseTime()`
- **Satisfaction** tracked via feedback form ? `GamificationService::updateSatisfactionScore()`

### ?? Scheduled Jobs

Add to `app/Console/Kernel.php`:
``php
\->job(new \App\Jobs\CRM\UpdateAllLeaderboardsJob())
    ->dailyAt('00:30') // Run at 12:30 AM daily
    ->name('gamification:update-leaderboards');
``

### ?? Testing Requirements (To Be Implemented)

- **Unit Tests:**
  - `GamificationServiceTest` � Score calculations, point awards, badge criteria
  - `GamificationRepositoryTest` � CRUD operations, leaderboard ranking logic
  
- **Feature Tests:**
  - `GamificationDashboardTest` � Web route, view rendering, data display
  - `LeaderboardComponentTest` � Livewire interactions
  
- **Integration Tests:**
  - Badge awarding flow end-to-end
  - Leaderboard ranking updates on score changes
  - Multi-tenant data isolation

### ?? Deployment Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Seed badges: `php artisan db:seed --class=BadgeSeeder`
- [ ] Configure queue worker for `crm-gamification` queue
- [ ] Schedule `UpdateAllLeaderboardsJob` in cron
- [ ] Grant `crm.leads.view` permission to counsellor roles
- [ ] Add gamification menu item to navigation sidebar

### ?? Next Steps

- Integrate service calls into existing CRM event listeners
- Build notification system for badge earning (email/Slack)
- Add gamification analytics to Analytics module (CRM-AR)
- Implement AI-powered coaching suggestions based on performance gaps
- Add leaderboard export (CSV/PDF) for managers

---

**Implemented by:** GitHub Copilot (AI Assistant)  
**Date:** April 13, 2026  
**Status:** ? Ready for Testing & Integration
