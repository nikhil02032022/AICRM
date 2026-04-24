# Sprint 5 - Group Z: Extended Alumni — Referral and NPS

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** Z
**Module:** Alumni Lifecycle Management
**Req IDs:** CRM-AL-002, CRM-AL-003, CRM-AL-004
**Status:** Pending
**Dependencies:** AlumniPipeline model (Sprint 4 Group W), Lead model (Sprint 1 Group A), PublicFormActor (Sprint 1 Group B), LeadAttribution (Sprint 2 Group H), Communication engine (Sprint 1 Group F), Executive Dashboard (Sprint 4 Group V)

---

## Objective

Extend the alumni lifecycle beyond the initial pipeline bridge (AL-001, Sprint 4) with referral campaign management, lead attribution through unique alumni referral codes, reward accrual on conversion, and surfacing of alumni NPS scores on the executive analytics dashboard — completing the full alumni engagement loop from graduation through active advocacy.

## In Scope

**AL-002 — Alumni Referral Campaigns:**
1. Referral campaign management: create, edit, activate, deactivate, archive campaigns with name, description, start/end dates, reward type (gift voucher, fee waiver, recognition), reward value, and status.
2. Unique referral code generation per alumni per active campaign (8-character alphanumeric, institution-unique).
3. Referral code sharing: WhatsApp message template with embedded referral link and email sharing.
4. Alumni referral dashboard card showing their active codes, leads referred, and conversions.

**AL-003 — Referral Lead Tracking and Reward:**
1. Referral code capture on lead creation via `?ref=CODE` query parameter appended to web enquiry form URLs.
2. Lead record tagged with referring alumni ID, referral code, and campaign ID on creation.
3. Lead source set to `alumni_referral` in LeadAttribution for analytics.
4. Alumni reward accrual event fired when referred lead status changes to Enrolled (ERP handoff).
5. Reward tracking: `AlumniReferralCode.conversions_count` incremented; reward status updated.
6. Referral conversion report card in Analytics dashboard: leads referred, conversions, conversion rate per campaign.

**AL-004 — Alumni NPS Analytics Integration:**
1. AlumniNpsSnapshot model storing NPS score, promoters %, detractors %, neutral %, per institution, academic year, and optional programme.
2. Admin manual-entry UI for importing NPS data (for institutions without A2A Alumni module integration).
3. Webhook endpoint `POST /api/crm/v1/alumni/nps-sync` for automated NPS push from A2A Alumni module.
4. NPS trend card with score history chart added to existing Executive Dashboard view.

## Out of Scope

- Native alumni portal or app (Phase 2 / MB module).
- Full A2A Alumni module bidirectional sync (ERP integration deferred).
- NPS survey creation or sending (owned by A2A Alumni module, not CRM).
- Alumni commissions (separate from referral rewards — agent commission model from Sprint 2 Group U applies to agents, not alumni).

## Dependencies

1. `AlumniPipeline` model from Sprint 4 Group W — referenced as alumni source in referral codes.
2. `Lead` model from Sprint 1 Group A — extended with referred_by_alumni_id, referral_code, referral_campaign_id columns.
3. `PublicFormActor` from Sprint 1 Group B — modified to capture `?ref=CODE` parameter on form submission.
4. `LeadAttribution` model from Sprint 2 Group H — used to record alumni_referral source with referral code metadata.
5. Communication engine (Sprint 1 Group F) — WhatsApp and email for referral code sharing.
6. Executive Dashboard view from Sprint 4 Group V — NPS card added.
7. `ApplicationStatusHistory` and `ApplicationConversionLog` from Sprint 3 Group N — for enrollment detection triggering reward accrual.
8. `InstitutionScope` and `CampusScope` traits — applied to all new models.

## Design Notes

1. Referral code generation uses `str_pad(strtoupper(base_convert(crc32(uniqid()), 10, 36)), 8, '0', STR_PAD_LEFT)` — collision-checked against existing codes in DB before saving.
2. `?ref=CODE` parameter captured in `PublicFormActor::handle()` — if code is valid and campaign active, tag lead; if expired or invalid, log warning but create lead without referral tag (no error shown to visitor).
3. Reward accrual: observe `ApplicationConversionLog` model — when a lead with `referred_by_alumni_id` converts, dispatch `AlumniReferralConvertedJob` which updates reward status and notifies admin.
4. NPS data: formula — NPS = (Promoters% - Detractors%); displayed as single NPS score (-100 to +100) with colour coding: green >50, amber 0–50, red <0.
5. All new models use InstitutionScope; academic_year_id from AcademicYear model (Sprint 4 Group W).

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for alumni referral campaign management and NPS analytics.
3. Group Z test cases document (`test-cases/sprint5_group_Z_test_cases.md`).
4. Master tracker status and remarks update.

## Acceptance Gates

1. Admin can create an alumni referral campaign and generate referral codes for alumni.
2. Referral code can be shared via WhatsApp and email from the alumni record.
3. Lead created via `?ref=VALID_CODE` URL is tagged with alumni ID and campaign ID.
4. Lead created via `?ref=EXPIRED_CODE` URL is created normally without error — referral tag is not applied.
5. When a referred lead is enrolled, reward status updates to earned on the referral code record.
6. Analytics dashboard shows referral conversion count and rate per campaign.
7. Admin can enter NPS data manually; Executive Dashboard shows current NPS score with trend.
8. NPS webhook endpoint accepts valid JSON payload and updates AlumniNpsSnapshot.
9. No cross-institution referral code acceptance (institution_id enforced).

## Risks and Mitigation

1. Referral code collisions across high-volume campaigns:
   Mitigation: Code generation includes DB uniqueness check with up to 5 retry attempts before failing with a logged error; unique index on (institution_id, code) in migration.
2. PublicFormActor modification breaking existing form submissions:
   Mitigation: Referral code capture is wrapped in a try-catch; any failure logs a warning but does not interrupt the form submission pipeline.
3. NPS data accuracy if manually entered:
   Mitigation: Admin entry form requires total (promoters + neutrals + detractors) to equal 100%; client-side validation enforced.

## Exit Criteria

1. AL-002, AL-003, AL-004 marked completed in master tracker.
2. ~22 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. QA sign-off recorded.

---

## File Manifest

### Migrations
- `database/migrations/2026_05_02_000001_create_alumni_referral_campaigns_table.php` — id, institution_id, name, description, start_date, end_date, reward_type (enum), reward_value (decimal 10,2 nullable), status (enum: draft/active/paused/ended), created_by, created_at, updated_at
- `database/migrations/2026_05_02_000002_create_alumni_referral_codes_table.php` — id, institution_id, campaign_id (FK), alumni_id (FK alumni_pipeline), code (string 8, unique per institution), is_active (boolean), conversions_count (unsignedInteger default 0), reward_status (enum: pending/earned/disbursed), shared_at (timestamp nullable), expires_at (timestamp nullable), created_at, updated_at
- `database/migrations/2026_05_02_000003_add_referral_fields_to_leads.php` — adds referred_by_alumni_id (nullable FK alumni_pipeline), referral_code (nullable string 8), referral_campaign_id (nullable FK alumni_referral_campaigns)
- `database/migrations/2026_05_02_000004_create_alumni_nps_snapshots_table.php` — id, institution_id, academic_year_id, programme_id (nullable), nps_score (smallInteger), promoters_pct (decimal 5,2), neutrals_pct (decimal 5,2), detractors_pct (decimal 5,2), survey_date (date), source (enum: manual/webhook), created_at, updated_at

### Enums
- `App\Enums\CRM\Alumni\ReferralCampaignStatus` — Draft, Active, Paused, Ended
- `App\Enums\CRM\Alumni\ReferralRewardType` — GiftVoucher, FeeWaiver, Recognition
- `App\Enums\CRM\Alumni\ReferralRewardStatus` — Pending, Earned, Disbursed
- `App\Enums\CRM\Alumni\NpsSnapshotSource` — Manual, Webhook

### Models
- `App\Models\CRM\Alumni\AlumniReferralCampaign` — uses InstitutionScope; relationships to AlumniReferralCode
- `App\Models\CRM\Alumni\AlumniReferralCode` — uses InstitutionScope; relationships to AlumniPipeline, AlumniReferralCampaign, Lead
- `App\Models\CRM\Alumni\AlumniNpsSnapshot` — uses InstitutionScope; relationships to AcademicYear
- `App\Models\CRM\Lead` — updated: add referred_by_alumni_id, referral_code, referral_campaign_id (existing model)

### Services
- `App\Services\CRM\Alumni\AlumniReferralService` — generateCode(AlumniPipeline, Campaign): AlumniReferralCode; trackReferral(string code, Lead): void; accrueReward(AlumniReferralCode): void; getStats(Campaign): array
- `App\Services\CRM\Alumni\AlumniNpsService` — storeSnapshot(array): AlumniNpsSnapshot; getLatestScore(Institution): int; getTrend(Institution, int months): array

### Jobs
- `App\Jobs\CRM\Alumni\AlumniReferralConvertedJob` — triggered on enrolment; calls AlumniReferralService::accrueReward(); queued on crm-alumni
- `App\Jobs\CRM\Alumni\SendReferralCodeJob` — sends referral code via WhatsApp and email to alumni; queued on crm-comms-email and crm-comms-sms

### Observers
- `App\Observers\CRM\Alumni\ApplicationConversionReferralObserver` — listens to ApplicationConversionLog::created; if lead has referred_by_alumni_id, dispatches AlumniReferralConvertedJob

### Controllers (Web)
- `App\Http\Controllers\CRM\Alumni\AlumniReferralCampaignController` — index, create, store, edit, update, destroy, activate, pause
- `App\Http\Controllers\CRM\Alumni\AlumniReferralCodeController` — index (per campaign), generate, share (WhatsApp/email)
- `App\Http\Controllers\CRM\Admin\AlumniNpsController` — index, create, store (manual NPS entry)

### Controllers (API)
- `App\Http\Controllers\CRM\Api\AlumniNpsSyncController` — store (webhook: POST /api/crm/v1/alumni/nps-sync)

### Views (Blade)
- `resources/views/crm/alumni/referral-campaigns/index.blade.php`
- `resources/views/crm/alumni/referral-campaigns/create.blade.php`
- `resources/views/crm/alumni/referral-campaigns/edit.blade.php`
- `resources/views/crm/alumni/referral-codes/index.blade.php`
- `resources/views/crm/admin/nps/index.blade.php`
- `resources/views/crm/admin/nps/create.blade.php`
- `resources/views/crm/analytics/dashboards/executive.blade.php` — updated: add NPS score card with trend sparkline

### Notifications
- `App\Notifications\CRM\Alumni\ReferralCodeShareNotification` — email and WhatsApp; includes referral link, code, and campaign details

### Policies
- `App\Policies\CRM\Alumni\AlumniReferralCampaignPolicy` — manage (manager/admin), view (counsellor)
- `App\Policies\CRM\Alumni\AlumniNpsPolicy` — manage (admin)

### Seeders
- `Database\Seeders\CRM\Alumni\AlumniReferralPermissionSeeder` — alumni.referral.manage, alumni.referral.view, alumni.nps.manage permissions

### Tests
- `tests/Unit/CRM/Alumni/AlumniReferralServiceTest.php`
- `tests/Unit/CRM/Alumni/AlumniNpsServiceTest.php`
- `tests/Feature/CRM/Alumni/ReferralCampaignCrudTest.php`
- `tests/Feature/CRM/Alumni/ReferralCodeCaptureTest.php`
- `tests/Feature/CRM/Alumni/ReferralConversionRewardTest.php`
- `tests/Feature/CRM/Alumni/NpsSnapshotTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| AL-002 | Alumni referral campaigns shall generate unique referral codes for alumni to share with prospective students | `AlumniReferralCampaign`, `AlumniReferralCode` models, `AlumniReferralService::generateCode()`, `ReferralCodeShareNotification`, `AlumniReferralCampaignController`, `AlumniReferralCodeController` |
| AL-003 | Leads generated via alumni referral shall be tagged and tracked for conversion, enabling alumni reward programmes | `PublicFormActor` updated for `?ref=CODE` capture, `Lead` model referral columns, `ApplicationConversionReferralObserver`, `AlumniReferralConvertedJob`, `AlumniReferralService::accrueReward()` |
| AL-004 | Alumni NPS scores shall be surfaceable on CRM analytics dashboard as a lead conversion influencer | `AlumniNpsSnapshot` model, `AlumniNpsService`, `AlumniNpsController`, `AlumniNpsSyncController` (webhook), NPS card on Executive Dashboard |

---

## Security Checklist

- [ ] Referral campaign and code management routes protected by `auth` and `permission:alumni.referral.manage` middleware.
- [ ] NPS webhook endpoint authenticated via Sanctum token — not publicly accessible.
- [ ] AlumniReferralCampaignPolicy enforces institution scoping — admin cannot view campaigns of another institution.
- [ ] Referral code uniqueness constraint at DB level (unique index) prevents race-condition duplicates.
- [ ] PublicFormActor referral capture wrapped in try-catch — code validation failure never exposes internal error to visitor.
- [ ] DPDP: referred lead's personal data subject to same consent and erasure controls as any lead record.
- [ ] `?ref=CODE` parameter sanitised (alphanumeric only, max 8 chars) before DB lookup to prevent injection.

---

## Implementation Log

**Status:** Pending — implementation not yet started.

### Planned Phases

**Phase A — Migrations**
- Alumni referral campaigns, referral codes, lead referral fields, NPS snapshots

**Phase B — Enums**
- ReferralCampaignStatus, ReferralRewardType, ReferralRewardStatus, NpsSnapshotSource

**Phase C — Models and Services**
- AlumniReferralCampaign, AlumniReferralCode, AlumniNpsSnapshot models; AlumniReferralService, AlumniNpsService

**Phase D — Observer and Jobs**
- ApplicationConversionReferralObserver, AlumniReferralConvertedJob, SendReferralCodeJob

**Phase E — HTTP Layer**
- Controllers, API webhook controller, routes

**Phase F — Views**
- Campaign and code management views, NPS admin views, Executive Dashboard NPS card

**Phase G — Tests**
- Unit and Feature test files

**Estimated test count:** 22 test cases
