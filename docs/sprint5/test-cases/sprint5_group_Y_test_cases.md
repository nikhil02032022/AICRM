# Sprint 5 Group Y — Test Cases

**BRD Req IDs:** CRM-EC-018, CRM-EC-019
**Generated:** 2026-04-24
**Total Test Cases:** 22

---

## Unit Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-Y-U-001 | EC-018 | VideoMeetingService resolves GoogleMeetProvider when provider config is google_meet | Returns GoogleMeetProvider instance | VideoMeetingServiceTest |
| TC-Y-U-002 | EC-018 | VideoMeetingService resolves ZoomProvider when provider config is zoom | Returns ZoomProvider instance | VideoMeetingServiceTest |
| TC-Y-U-003 | EC-018 | ZoomProvider returns institution Zoom room URL from SystemConfig | Returns configured URL string without API call | VideoMeetingServiceTest |
| TC-Y-U-004 | EC-018 | VideoMeetingService returns empty string when provider is none | Returns empty string; no exception | VideoMeetingServiceTest |
| TC-Y-U-005 | EC-019 | WalkInQueueService issues token with sequential token_number starting at 1 for new day | token_number = 1 for first token of day per campus | WalkInQueueServiceTest |
| TC-Y-U-006 | EC-019 | WalkInQueueService increments token_number sequentially within same campus and day | Third token has token_number = 3 | WalkInQueueServiceTest |
| TC-Y-U-007 | EC-019 | WalkInQueueService::callNext returns oldest waiting token for campus | Returns token with lowest token_number in Waiting status | WalkInQueueServiceTest |
| TC-Y-U-008 | EC-019 | WalkInQueueService::callNext returns null when no waiting tokens exist | Returns null; no exception | WalkInQueueServiceTest |
| TC-Y-U-009 | EC-019 | WalkInQueueService::dailyStats returns correct served_count, skipped_count, avg_wait_minutes | Correct aggregates returned for campus and date | WalkInQueueServiceTest |

---

## Feature Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-Y-F-001 | EC-018 | Counselling session creation triggers SendSessionVideoLinkJob dispatch | Job dispatched to queue; meeting_link column populated on session | VideoLinkGenerationTest |
| TC-Y-F-002 | EC-018 | Session detail page shows Join Video Call button when meeting_link is present | HTTP 200; response contains join meeting link URL | VideoLinkGenerationTest |
| TC-Y-F-003 | EC-018 | Session detail page shows no Join Video Call button when meeting_provider is none | Response does not contain join button element | VideoLinkGenerationTest |
| TC-Y-F-004 | EC-018 | Student portal appointment card shows Join Video Call button for session with meeting link | HTTP 200 portal dashboard; join link present in appointment card | VideoLinkGenerationTest |
| TC-Y-F-005 | EC-019 | POST to walk-in kiosk creates WalkInToken with status=Waiting | 201; token record exists with Waiting status and correct campus_id | WalkInTokenLifecycleTest |
| TC-Y-F-006 | EC-019 | POST callNext changes token status from Waiting to Called and sets called_at | Token status=Called, called_at not null | WalkInTokenLifecycleTest |
| TC-Y-F-007 | EC-019 | POST serve changes token status from Called to Served and sets served_at | Token status=Served, served_at not null | WalkInTokenLifecycleTest |
| TC-Y-F-008 | EC-019 | POST skip changes token status from Called to Skipped | Token status=Skipped | WalkInTokenLifecycleTest |
| TC-Y-F-009 | EC-019 | WalkInTokenCalled event is broadcast on walk-in.{campus_id} channel when counsellor calls next | Broadcast event fired with correct channel name and token data | WalkInBroadcastTest |
| TC-Y-F-010 | EC-019 | Queue display screen is accessible without authentication | HTTP 200 on GET /queue/{institution}/display | QueueDisplayPublicAccessTest |
| TC-Y-F-011 | EC-019 | Queue display screen does not contain any personal data (name/phone) when visitor opted not to share | Response does not contain any name or phone fields in output | QueueDisplayPublicAccessTest |
| TC-Y-F-012 | EC-019 | Counsellor cannot manage tokens from another campus (cross-campus scoping) | 403 Forbidden on callNext for token from different campus | WalkInTokenLifecycleTest |
| TC-Y-F-013 | EC-019 | Walk-in token issued via kiosk optionally creates lead stub with programme interest | Lead record created with source=walk_in when name and mobile provided | WalkInTokenLifecycleTest |

---

## Coverage Notes

- EC-018 covered by TC-Y-U-001 to TC-Y-U-004 and TC-Y-F-001 to TC-Y-F-004
- EC-019 covered by TC-Y-U-005 to TC-Y-U-009 and TC-Y-F-005 to TC-Y-F-013
- Multi-tenancy isolation verified in: TC-Y-F-012 (campus scoping)
- Broadcast real-time functionality verified in: TC-Y-F-009
- Public access without PII exposure verified in: TC-Y-F-010, TC-Y-F-011
- DPDP compliance: walk-in visitor data is voluntary; kiosk does not require name/mobile; verified in TC-Y-F-011
- Graceful fallback (no video provider) verified in: TC-Y-U-004, TC-Y-F-003
