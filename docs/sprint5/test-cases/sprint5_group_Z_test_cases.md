# Sprint 5 Group Z — Test Cases

**BRD Req IDs:** CRM-AL-002, CRM-AL-003, CRM-AL-004
**Generated:** 2026-04-24
**Total Test Cases:** 22

---

## Unit Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-Z-U-001 | AL-002 | AlumniReferralService::generateCode produces an 8-character alphanumeric string | Code length = 8; matches /^[A-Z0-9]{8}$/ | AlumniReferralServiceTest |
| TC-Z-U-002 | AL-002 | AlumniReferralService::generateCode retries on collision and produces a unique code | On simulated collision, retry succeeds and unique code returned | AlumniReferralServiceTest |
| TC-Z-U-003 | AL-002 | AlumniReferralService::generateCode throws after 5 collision retries | RuntimeException thrown after 5 failed attempts | AlumniReferralServiceTest |
| TC-Z-U-004 | AL-003 | AlumniReferralService::trackReferral sets lead.referred_by_alumni_id and referral_code | Lead record updated with alumni ID and code | AlumniReferralServiceTest |
| TC-Z-U-005 | AL-003 | AlumniReferralService::trackReferral does nothing if referral code is expired or inactive | Lead created without referral fields; no exception | AlumniReferralServiceTest |
| TC-Z-U-006 | AL-003 | AlumniReferralService::accrueReward sets reward_status to Earned and increments conversions_count | AlumniReferralCode.reward_status=Earned; conversions_count incremented by 1 | AlumniReferralServiceTest |
| TC-Z-U-007 | AL-004 | AlumniNpsService::getLatestScore returns NPS score as integer in range -100 to 100 | Returned value is integer; formula: (promoters - detractors) | AlumniNpsServiceTest |
| TC-Z-U-008 | AL-004 | AlumniNpsService::getTrend returns array of monthly snapshots ordered by survey_date ascending | Array ordered ascending; each element has survey_date and nps_score | AlumniNpsServiceTest |

---

## Feature Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-Z-F-001 | AL-002 | Admin creates referral campaign via POST; campaign is stored with Draft status | 201 or redirect; campaign in DB with status=Draft | ReferralCampaignCrudTest |
| TC-Z-F-002 | AL-002 | Admin activates a Draft campaign; status changes to Active | Campaign status=Active after PATCH activate | ReferralCampaignCrudTest |
| TC-Z-F-003 | AL-002 | Admin cannot view referral campaigns from another institution | 404 on GET for cross-institution campaign | ReferralCampaignCrudTest |
| TC-Z-F-004 | AL-002 | POST generate referral code for alumni creates AlumniReferralCode with unique code | 201; code length=8; institution_id matches alumni's institution | ReferralCampaignCrudTest |
| TC-Z-F-005 | AL-003 | Lead created via GET /f/{slug}?ref=VALIDCODE tags lead with alumni ID and code | Lead.referred_by_alumni_id not null; referral_code=VALIDCODE | ReferralCodeCaptureTest |
| TC-Z-F-006 | AL-003 | Lead created via GET /f/{slug}?ref=EXPIREDCODE is created normally without error | HTTP 200; lead created; Lead.referred_by_alumni_id is null | ReferralCodeCaptureTest |
| TC-Z-F-007 | AL-003 | Lead created without ?ref param has no referral fields set | Lead.referred_by_alumni_id null; referral_code null | ReferralCodeCaptureTest |
| TC-Z-F-008 | AL-003 | Lead source set to alumni_referral in LeadAttribution when referral code captured | LeadAttribution record with source=alumni_referral exists | ReferralCodeCaptureTest |
| TC-Z-F-009 | AL-003 | ApplicationConversionLog creation for referred lead dispatches AlumniReferralConvertedJob | Job dispatched; AlumniReferralCode.reward_status set to Earned | ReferralConversionRewardTest |
| TC-Z-F-010 | AL-003 | AlumniReferralConvertedJob does NOT fire for non-referred leads (no referred_by_alumni_id) | No AlumniReferralCode record updated | ReferralConversionRewardTest |
| TC-Z-F-011 | AL-004 | Admin creates NPS snapshot via POST with valid promoters/neutrals/detractors summing to 100 | AlumniNpsSnapshot created; nps_score calculated correctly | NpsSnapshotTest |
| TC-Z-F-012 | AL-004 | Admin POST with promoters + neutrals + detractors ≠ 100 returns 422 validation error | 422; validation error on totals | NpsSnapshotTest |
| TC-Z-F-013 | AL-004 | Executive dashboard includes NPS score card section | HTTP 200 on executive dashboard; response contains NPS score value | NpsSnapshotTest |
| TC-Z-F-014 | AL-004 | NPS webhook endpoint accepts valid JSON payload and creates snapshot | 200; AlumniNpsSnapshot record created with source=webhook | NpsSnapshotTest |

---

## Coverage Notes

- AL-002 covered by TC-Z-U-001 to TC-Z-U-003 and TC-Z-F-001 to TC-Z-F-004
- AL-003 covered by TC-Z-U-004 to TC-Z-U-006 and TC-Z-F-005 to TC-Z-F-010
- AL-004 covered by TC-Z-U-007 to TC-Z-U-008 and TC-Z-F-011 to TC-Z-F-014
- Multi-tenancy isolation verified in: TC-Z-F-003 (cross-institution campaign denial)
- Referral code collision handling verified in: TC-Z-U-002, TC-Z-U-003
- Graceful degradation (expired/invalid code) verified in: TC-Z-U-005, TC-Z-F-006
- DPDP compliance: referral tracking only stores alumni_id and a code (no personal data beyond existing lead record); NPS data is aggregate institution-level scores — no individual survey responses stored in CRM
