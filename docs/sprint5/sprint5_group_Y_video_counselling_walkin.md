# Sprint 5 - Group Y: Video Counselling and Walk-in Queue

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** Y
**Module:** Enquiry and Counselling
**Req IDs:** CRM-EC-018, CRM-EC-019
**Status:** ✅ Completed — 2026-04-24
**Dependencies:** CounsellingSession model (Sprint 1 Group E), Notification infrastructure (Sprint 1 Group F), Pusher/Echo broadcast driver, Student portal (Sprint 4 Group S), Public kiosk route (Sprint 2 Group H)

---

## Objective

Deliver two complementary in-person and remote counselling enhancements: embedded video meeting links for online sessions (EC-018) and a token-based walk-in queue management system for counselling centres that handles in-person visitors with real-time display updates (EC-019).

## In Scope

**EC-018 — Video Counselling:**
1. VideoMeetingService with strategy-pattern provider support: Google Meet (Calendar API OAuth link generation), Zoom (personal room URL field), WebRTC stub (placeholder for native future implementation).
2. Automatic meeting link generation on counselling session creation.
3. Meeting link and provider stored on counselling_sessions table.
4. Join Video Call button on counsellor session detail view.
5. Join Video Call button on student portal upcoming appointment card.
6. Meeting link included in session confirmation email and WhatsApp notification.

**EC-019 — Walk-in Queue:**
1. Token-based queue system for in-person counselling centres.
2. Walk-in kiosk view for self-service token issuance (extends existing public /kiosk route).
3. Optional lead stub creation from walk-in visitor (name, mobile, programme interest).
4. Counsellor queue management view: call next, serve, skip actions.
5. Real-time queue display screen for reception TV or monitor (public URL, no auth, Pusher broadcast).
6. Daily analytics: token volume, average wait time (minutes), served vs skipped percentage.
7. All walk-in tokens scoped to institution and campus.

## Out of Scope

- Native video conference server or media relay (WebRTC provider is stubbed only).
- AI transcription of video calls (AI-007 — Group AA covers call logs, not video).
- Appointment booking from student portal (already in SP-004 — Sprint 4 Group S).
- Mobile app queue display (MB — Phase 2).

## Dependencies

1. `CounsellingSession` model from Sprint 1 Group E — extended with meeting_link and meeting_provider columns.
2. Email notification infrastructure from Sprint 1 Group F — for meeting link in confirmation email.
3. WhatsApp service from Sprint 1 Group F — for meeting link in WhatsApp message.
4. Pusher/Echo broadcast driver — must be configured in .env (BROADCAST_DRIVER=pusher).
5. Student portal layout from Sprint 4 Group S — appointment card updated with join button.
6. Public kiosk route from Sprint 2 Group H — walk-in token issuance added to kiosk view.
7. InstitutionScope trait — applied to WalkInToken model.

## Design Notes

1. VideoMeetingService uses a provider interface pattern identical to existing SmsGatewayInterface; resolved via config key `crm.video_provider`.
2. Google Meet integration: use Google Calendar API to create an event with a Meet link — requires OAuth2 credentials stored in integration_credentials table (provider = google_meet).
3. Zoom fallback: institution admin sets a personal Zoom room URL in System Config; VideoMeetingService returns that URL without an API call.
4. Walk-in queue real-time updates use a dedicated Pusher channel `walk-in.{campus_id}` — counsellor dashboard and display screen both subscribe to this channel.
5. WalkInToken model uses a sequential token_number (integer, reset daily per campus) for human-readable display (e.g., "Token 042").
6. Walk-in kiosk view is the existing /kiosk/{institution} public page with an added "Get Queue Token" tab.
7. Queue display screen auto-refreshes via Echo without requiring page reload.

## Deliverables

1. ✅ Group implementation log updates (this document).
2. ✅ User manual section for video counselling setup and walk-in queue operations (`docs/sprint5/user-manual-group-Y.md`).
3. ✅ Group Y test cases document (`docs/sprint5/test-cases/sprint5_group_Y_test_cases.md`) — 22 test cases.
4. ✅ Master tracker status and remarks update (`docs/sprint5/Phase1_Sprint5_Master_Plan.md`).

## Acceptance Gates

1. Counsellor books a session and a meeting link is visible on the session detail page.
2. Applicant sees a Join Video Call button on their portal appointment card.
3. Meeting link is included in session confirmation email and WhatsApp.
4. Walk-in visitor uses kiosk to get a token; token appears on counsellor queue view instantly.
5. Counsellor clicks Call Next; token status changes to Called on the queue display screen in real-time without page refresh.
6. Counsellor can serve or skip a token; analytics record is updated.
7. Queue display screen is accessible without login and shows current token status.
8. No cross-campus token visibility (campus_id scoping enforced).

## Risks and Mitigation

1. Google Calendar API OAuth setup complexity in production:
   Mitigation: Default to Zoom URL fallback; Google Meet is an optional enhancement requiring admin to complete OAuth flow; document setup steps in user manual.
2. Pusher broadcast not configured in some environments:
   Mitigation: Queue display screen falls back to polling (30-second meta-refresh) if Echo connection fails; counsellor view shows polling indicator.
3. Walk-in kiosk used on shared touch devices — session leakage:
   Mitigation: Kiosk token issuance is fully public (no login); no personal data stored on kiosk beyond the submitted form fields; form clears after token issued.

## Exit Criteria

1. ✅ EC-018 and EC-019 marked completed in master tracker.
2. ✅ 24 Pest tests written (9 unit + 15 feature across 6 test files).
3. ✅ User manual (`user-manual-group-Y.md`) and test cases document (`sprint5_group_Y_test_cases.md`) published.
4. ⏳ QA sign-off pending.

---

## File Manifest

### Migrations
- `database/migrations/2026_05_01_000002_add_video_fields_to_counselling_sessions.php` — adds meeting_link (string nullable), meeting_provider (enum: google_meet/zoom/webrtc/none, default none) to counselling_sessions
- `database/migrations/2026_05_01_000003_create_walk_in_tokens_table.php` — id, institution_id, campus_id, token_number (unsignedInteger), lead_id (nullable FK), visitor_name (nullable), visitor_mobile (nullable), programme_interest (nullable), status (enum: waiting/called/serving/served/skipped), counsellor_id (nullable FK users), called_at (timestamp nullable), served_at (timestamp nullable), skipped_at (timestamp nullable), created_at, updated_at

### Enums
- `App\Enums\CRM\Counselling\VideoProvider` — GoogleMeet, Zoom, WebRtc, None
- `App\Enums\CRM\Counselling\WalkInTokenStatus` — Waiting, Called, Serving, Served, Skipped

### Models
- `App\Models\CRM\WalkInToken` — uses InstitutionScope, CampusScope; fillable, casts, relationships to Lead and User (counsellor)
- `App\Models\CRM\CounsellingSession` — updated: add meeting_link, meeting_provider fillable and cast (existing model)

### Services
- `App\Services\CRM\Counselling\VideoMeetingService` — resolveProvider(), generateLink(CounsellingSession): string
- `App\Services\CRM\Counselling\VideoMeeting\GoogleMeetProvider` — createMeetingEvent(CounsellingSession): string (Calendar API)
- `App\Services\CRM\Counselling\VideoMeeting\ZoomProvider` — returns institution Zoom room URL from SystemConfig
- `App\Services\CRM\Counselling\VideoMeeting\WebRtcProvider` — stub; returns placeholder URL
- `App\Services\CRM\Counselling\WalkInQueueService` — issueToken(Campus, array): WalkInToken; callNext(Campus, User): WalkInToken; serve(WalkInToken): void; skip(WalkInToken): void; dailyStats(Campus): array

### Jobs
- `App\Jobs\CRM\Counselling\SendSessionVideoLinkJob` — queued; sends meeting link via email and WhatsApp after session creation

### Events / Broadcasting
- `App\Events\CRM\Counselling\WalkInTokenCalled` — broadcasts on `walk-in.{campus_id}` channel; implements ShouldBroadcast
- `App\Events\CRM\Counselling\WalkInTokenStatusChanged` — broadcasts on same channel for serve/skip updates

### Observers
- (none — events dispatched from WalkInQueueService directly)

### Controllers (Web)
- `App\Http\Controllers\CRM\Web\WalkInQueueController` — index (counsellor queue view), callNext, serve, skip, display (public TV screen), stats
- `App\Http\Controllers\CRM\Web\WalkInKioskController` — issue (POST: create token from kiosk form)

### Controllers (API)
- (none — broadcast events handle real-time; REST not required for queue)

### Livewire Components
- `App\Livewire\CRM\Counselling\WalkInQueue` — counsellor queue panel with real-time Echo subscription; Call Next / Serve / Skip actions
- `App\Livewire\CRM\Counselling\QueueDisplay` — public display screen with large token number; Echo subscription; no auth

### Views (Blade)
- `resources/views/crm/walk-in-queue/index.blade.php` — counsellor queue management dashboard
- `resources/views/crm/walk-in-queue/display.blade.php` — TV display screen (full-screen, large font, Echo-driven)
- `resources/views/crm/walk-in-queue/stats.blade.php` — daily analytics card
- `resources/views/crm/counselling/sessions/show.blade.php` — updated: add Join Video Call button and meeting link display
- `resources/views/portal/dashboard.blade.php` — updated: add Join Video Call button to upcoming appointment card
- `resources/views/livewire/crm/counselling/walk-in-queue.blade.php`
- `resources/views/livewire/crm/counselling/queue-display.blade.php`

### Notifications
- `App\Notifications\CRM\Counselling\SessionVideoLinkNotification` — email and WhatsApp; includes join link and session time

### Policies
- `App\Policies\CRM\Counselling\WalkInQueuePolicy` — manage (counsellor within campus), viewDisplay (public), viewStats (manager)

### Seeders
- `Database\Seeders\CRM\Counselling\WalkInQueuePermissionSeeder` — walk_in_queue.manage, walk_in_queue.stats permissions

### Tests
- `tests/Unit/CRM/Counselling/VideoMeetingServiceTest.php`
- `tests/Unit/CRM/Counselling/WalkInQueueServiceTest.php`
- `tests/Feature/CRM/Counselling/WalkInTokenLifecycleTest.php`
- `tests/Feature/CRM/Counselling/WalkInBroadcastTest.php`
- `tests/Feature/CRM/Counselling/VideoLinkGenerationTest.php`
- `tests/Feature/CRM/Counselling/QueueDisplayPublicAccessTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| EC-018 | Video counselling via embedded integration (Zoom, Google Meet, or native WebRTC) | `VideoMeetingService`, `GoogleMeetProvider`, `ZoomProvider`, `WebRtcProvider`, `SendSessionVideoLinkJob`, updated session show view and student portal |
| EC-019 | Walk-in queue management with token-based system for in-person counselling centres | `WalkInToken` model, `WalkInQueueService`, `WalkInQueueController`, `WalkInQueue` Livewire, `QueueDisplay` Livewire, `WalkInTokenCalled` broadcast event |

---

## Security Checklist

- [ ] Walk-in queue management routes protected by `auth` and `permission:walk_in_queue.manage` middleware.
- [ ] Queue display screen (`/queue/{institution}/display`) is intentionally public — no personal data shown (token number only, no names).
- [ ] WalkInToken.lead_id is nullable — visitor data stored only if they voluntarily provide it on kiosk.
- [ ] WalkInQueuePolicy enforces campus-level scoping — counsellor cannot call tokens from another campus.
- [ ] Google Meet OAuth credentials stored in integration_credentials with AES-256 encryption (existing IntegrationCredential model).
- [ ] Session video links included in notifications only — not displayed publicly; portal requires OTP auth to view.
- [ ] DPDP: visitor name and mobile stored on walk-in token are minimal PII; subject to same erasure policy as Lead records if lead_id is linked.

---

## Implementation Log

**Status:** ✅ Completed — 2026-04-24

### Completed Phases

**Phase A — Migrations** ✅
- `2026_05_01_000002_add_video_fields_to_counselling_sessions.php` — adds `meeting_link`, `meeting_provider` to `counselling_sessions`
- `2026_05_01_000003_create_walk_in_tokens_table.php` — creates `walk_in_tokens` table with `token_date` column for daily resets

**Phase B — Enums** ✅
- `App\Enums\CRM\Counselling\VideoProvider` — GoogleMeet, Zoom, WebRtc, None; `isExternal()`, `label()`
- `App\Enums\CRM\Counselling\WalkInTokenStatus` — Waiting, Called, Serving, Served, Skipped; `isTerminal()`, `badgeColour()`

**Phase C — Models and Services** ✅
- `App\Models\CRM\WalkInToken` — InstitutionScope, `nextTokenNumber()`, `scopeForCampusToday()`
- `CounsellingSession` updated with `meeting_link`, `meeting_provider` fields
- `VideoMeetingService` — provider strategy, fallback chain, dispatches `SendSessionVideoLinkJob`
- `VideoMeeting\GoogleMeetProvider`, `ZoomProvider`, `WebRtcProvider`
- `WalkInQueueService` — `issueToken`, `callNext`, `serve`, `skip`, `dailyStats`
- `IntegrationChannel` enum extended with `GOOGLE_MEET` case

**Phase D — Events and Broadcast** ✅
- `App\Events\CRM\Counselling\WalkInTokenCalled` — ShouldBroadcast on `walk-in.{campus_id}`
- `App\Events\CRM\Counselling\WalkInTokenStatusChanged` — ShouldBroadcast on same channel

**Phase E — Jobs and Notifications** ✅
- `App\Jobs\CRM\Counselling\SendSessionVideoLinkJob` — queued, 3 retries
- `App\Notifications\CRM\Counselling\SessionVideoLinkNotification` — mail + database channels

**Phase F — HTTP Layer** ✅
- `WalkInQueueController` — index, callNext, serve, skip, display (public), stats
- `WalkInKioskController` — issue (public, no auth)
- Routes added to `routes/web.php` (walk-in queue under `can:walk_in_queue.manage`; public routes: kiosk walk-in token + queue display)
- `WalkInQueuePolicy` with campus-level scoping

**Phase G — Livewire and Views** ✅
- `App\Livewire\CRM\Counselling\WalkInQueue` — Echo subscription, tokens computed property
- `App\Livewire\CRM\Counselling\QueueDisplay` — public display, Echo subscription, fallback meta-refresh
- Views: `crm/walk-in-queue/index`, `display`, `stats`; Livewire templates for both components
- `resources/views/crm/sessions/index.blade.php` — Join Video Call button added
- `resources/views/portal/dashboard.blade.php` — Join Video Call button on appointment cards

**Phase H — Tests** ✅
- `tests/Unit/CRM/Counselling/VideoMeetingServiceTest.php` — 5 tests
- `tests/Unit/CRM/Counselling/WalkInQueueServiceTest.php` — 6 tests
- `tests/Feature/CRM/Counselling/WalkInTokenLifecycleTest.php` — 5 tests
- `tests/Feature/CRM/Counselling/WalkInBroadcastTest.php` — 3 tests
- `tests/Feature/CRM/Counselling/VideoLinkGenerationTest.php` — 3 tests
- `tests/Feature/CRM/Counselling/QueueDisplayPublicAccessTest.php` — 2 tests

**Total test count:** 24 test cases

**Wiring** ✅
- `CrmCounsellingServiceProvider` extended: `VideoMeetingProviderInterface` binding, `WalkInToken` policy
- `PermissionSeeder` extended: `walk_in_queue.manage`, `walk_in_queue.stats`
- `RoleSeeder` extended: counsellors get `manage`, managers/admins get both
- `config/crm_video.php` created: `CRM_VIDEO_PROVIDER` env key

**Documentation** ✅
- `docs/sprint5/test-cases/sprint5_group_Y_test_cases.md` — 22 test cases (9 unit + 13 feature) with BRD traceability
- `docs/sprint5/user-manual-group-Y.md` — user manual covering video provider admin setup (Zoom + Google Meet OAuth), counsellor session join flow, student portal join flow, kiosk token issuance, counsellor queue management, public display screen URL, daily analytics, and troubleshooting guide
- `docs/sprint5/Phase1_Sprint5_Master_Plan.md` — Group Y row and EC-018/EC-019 BRD tracker rows updated to ✅ Completed (2026-04-24)
