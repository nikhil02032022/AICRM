# User Manual — Group Y: Video Counselling and Walk-in Queue

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Sprint:** 5 | **Group:** Y
**Req IDs:** CRM-EC-018, CRM-EC-019
**Last Updated:** 2026-04-24

---

## Overview

Group Y delivers two enhancements for counselling operations:

- **Video Counselling (EC-018):** Automatically generates a meeting link (Zoom, Google Meet, or WebRTC) when a counselling session is booked. Counsellors and students can join the call directly from their respective views.
- **Walk-in Queue (EC-019):** A token-based queue system for in-person counselling centres. Visitors collect a token from the self-service kiosk, counsellors manage the queue from their dashboard, and a public display screen shows the current token in real time.

---

## Role-Based Access

| Feature | Junior Counsellor | Senior Counsellor | Manager / Director | Admin |
|---|---|---|---|---|
| View Join Video Call button | ✓ | ✓ | ✓ | ✓ |
| Manage walk-in queue (call, serve, skip) | ✓ | ✓ | ✓ | ✓ |
| View queue stats / daily analytics | — | — | ✓ | ✓ |
| Configure video provider (System Config) | — | — | — | ✓ |

All queue operations are scoped to the counsellor's own campus. Counsellors cannot see or action tokens from another campus.

---

## Part 1 — Video Counselling (EC-018)

### 1.1 How It Works

When an admissions counsellor books a counselling session, the system automatically generates a meeting link based on the configured video provider:

- **Zoom:** Uses the institution's personal Zoom room URL set in System Config.
- **Google Meet:** Creates a Calendar event via the Google Meet OAuth integration and returns the generated Meet link.
- **WebRTC:** Returns a placeholder room URL for future native implementation.
- **None (default):** No link generated; Join Video Call button is not shown.

The meeting link is sent to the applicant via email notification after session creation.

### 1.2 Admin Setup — Configuring the Video Provider

**Required permission:** Institution Admin

1. Set the `CRM_VIDEO_PROVIDER` environment variable on your server:
   - `zoom` — use the institution's Zoom personal room URL (recommended default)
   - `google_meet` — use Google Calendar API (requires OAuth setup; see Section 1.3)
   - `none` — disable video links entirely

2. For Zoom: Navigate to **System Config → General Settings** and enter the institution's Zoom personal room URL in the **Zoom Room URL** field.

3. Save changes. New sessions created after this point will include the Zoom link automatically.

### 1.3 Admin Setup — Google Meet OAuth (Optional)

**Required permission:** Institution Admin

Google Meet integration requires a one-time OAuth2 authorisation flow:

1. In the Google Cloud Console, create a project and enable the **Google Calendar API**.
2. Create an OAuth 2.0 Client ID (Web application type).
3. In the CRM, navigate to **System Config → Integrations → Google Meet**.
4. Enter the Client ID and Client Secret from step 2.
5. Click **Authorise with Google** and complete the OAuth consent flow. The CRM stores the resulting refresh token encrypted in the integration credentials table.
6. Set `CRM_VIDEO_PROVIDER=google_meet` in your server environment and restart the application.

> **Note:** If Google Meet credentials are missing or the API call fails, the system automatically falls back to Zoom (if configured) rather than erroring.

### 1.4 Counsellor View — Joining a Session

1. Navigate to **Counselling → Sessions**.
2. Click on any session that has a meeting link (indicated by a video camera icon in the Actions column).
3. On the session detail page, click **Join Video Call**. The meeting link opens in a new browser tab.

### 1.5 Student Portal — Joining a Session

Students who have upcoming counselling sessions with a meeting link will see a **Join Video Call** button on their portal dashboard appointment card.

1. Log in to the student portal.
2. On the **Dashboard**, locate the **Upcoming Appointments** section.
3. Click **Join Video Call** on any appointment that has a link. The button is only shown when a meeting link exists.

---

## Part 2 — Walk-in Queue Management (EC-019)

### 2.1 How It Works

Walk-in visitors at a counselling centre collect a numbered token from the self-service kiosk. The system assigns sequential tokens (001, 002, 003…) that reset to 001 at midnight each day per campus. Counsellors manage the queue from their dashboard; a separate display screen (suitable for a reception TV) shows the current token in real time without requiring any login.

### 2.2 Visitor — Getting a Queue Token (Kiosk)

The kiosk is accessible at:

```
/kiosk/{institution-slug}
```

1. Open the kiosk URL on the reception tablet or touch screen.
2. Click the **Get Queue Token** tab.
3. Optionally enter your name, mobile number, and programme of interest.
4. Click **Get My Token**.
5. The screen displays your token number (e.g., **Token 007**). Note this number and wait to be called.

> Providing personal details is entirely optional. If provided, the system may create a lead record to follow up after your visit.

### 2.3 Counsellor — Managing the Queue

**Required permission:** `walk_in_queue.manage`

Navigate to **Counselling → Walk-in Queue** (or go directly to `/crm/walk-in-queue`).

The queue panel shows all waiting tokens for your campus today in order of arrival (lowest token number first).

#### Actions

| Button | When to use | What it does |
|---|---|---|
| **Call Next** | When ready for the next visitor | Marks the next waiting token as Called; triggers the display screen update |
| **Serve** | When the visitor has arrived and the session is underway | Marks the token as Serving → Served; records the served timestamp |
| **Skip** | If the visitor does not respond after being called | Marks the token as Skipped; moves to the next waiting token |

> The queue updates in real time via Pusher. If Pusher is not configured, the page refreshes every 30 seconds automatically.

#### Token Statuses

| Status | Badge colour | Meaning |
|---|---|---|
| Waiting | Grey | In queue, not yet called |
| Called | Blue | Counsellor has called this token |
| Serving | Amber | Visitor is with the counsellor |
| Served | Green | Session complete |
| Skipped | Red | Visitor did not respond; token bypassed |

### 2.4 Queue Display Screen (Reception TV)

The public display screen requires no login and is suitable for a TV or monitor in the waiting area:

```
/queue/{institution-uuid}/display
```

The screen shows:
- The **current token number** being called (large display)
- The status of the most recently actioned tokens
- Auto-refreshes in real time via Pusher; falls back to a 30-second page refresh if Pusher is unavailable

> **Important:** The display screen shows token numbers only — no visitor names or personal data are ever shown publicly.

### 2.5 Manager — Daily Analytics

**Required permission:** `walk_in_queue.stats`

Navigate to **Counselling → Walk-in Queue → Stats** (or `/crm/walk-in-queue/stats`).

The analytics tile shows today's figures for your campus:

| Metric | Description |
|---|---|
| Total Tokens | Total tokens issued today |
| Served | Tokens marked as Served |
| Skipped | Tokens marked as Skipped |
| Waiting | Tokens still in queue |
| Avg Wait Time | Average minutes from token issuance to Called status |

---

## Troubleshooting

| Symptom | Likely cause | Resolution |
|---|---|---|
| Join Video Call button not visible on session | `meeting_provider` is `none` or no provider configured | Set `CRM_VIDEO_PROVIDER` in server environment and ensure Zoom URL is configured in System Config |
| Meeting link not received in email | `SendSessionVideoLinkJob` failed or queue worker not running | Check Laravel queue workers; retry from the failed jobs table |
| Queue display screen not updating in real time | Pusher not configured or credentials incorrect | Check `PUSHER_*` environment variables; page falls back to 30-second refresh automatically |
| Counsellor sees 403 when calling a token | Token belongs to a different campus | Confirm the counsellor's campus assignment in User Management |
| Token numbers not resetting daily | `token_date` not populated correctly | Verify server timezone matches `APP_TIMEZONE` in `.env` |
| Google Meet link generation fails | OAuth credentials missing or expired | Re-authorise Google Meet in System Config → Integrations; system falls back to Zoom if available |
