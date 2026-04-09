# Lead Scoring Engine — User Manual

**Module:** Group D — Lead Scoring Engine  
**BRD Reference:** MEETCS-BRD-CRM-001 v1.0  
**BRD Requirements:** CRM-LQ-001 through CRM-LQ-008  
**Completed:** 9 April 2026  
**Audience:** Counsellors, Admissions Managers, Institution Admins

---

## 1. Overview

The A2A-CRM Lead Scoring Engine automatically calculates a quality score (0–100) for every lead based on seven weighted signal categories. The score indicates how likely a lead is to convert to a student, enabling counsellors to prioritise high-quality prospects efficiently.

Scores are **rule-based** and recalculated automatically whenever a lead's profile or activity changes. Each institution can customise the weights and temperature thresholds to match their admissions priorities.

> **Note:** AI-powered scoring (BRD LQ-003) is a Phase 2 (Should Have) feature. Group D implements the rule-based engine only.

---

## 2. Lead Temperature Classification

Every lead is automatically assigned a temperature label based on their score:

| Temperature | Default Score Range | Icon | Meaning |
|-------------|---------------------|------|---------|
| 🔥 **HOT**  | 75 – 100            | Red   | High conversion probability — act now |
| 🟡 **WARM** | 50 – 74             | Amber | Actively interested — nurture soon |
| 🔵 **COLD** | 0 – 49              | Blue  | Low engagement — monitor or drip nurture |

> Institution admins can change the HOT and WARM thresholds. See [Section 7 — Admin Configuration Guide](#7-admin-configuration-guide).

---

## 3. Score Breakdown — Signal Categories

The score is computed from seven signal categories. Default weights total 100 points.

| # | Signal Category | Default Max Points | What It Measures |
|---|----------------|--------------------|-----------------|
| 1 | **Profile Completeness** | 25 | First name, last name, mobile, email, city (5 fields — proportional) |
| 2 | **Programme Interest** | 20 | Whether the lead has selected a programme of interest |
| 3 | **Source Quality** | 20 | Lead acquisition channel (see tier table below) |
| 4 | **Engagement Level** | 20 | Status advancement beyond New Enquiry + counsellor assignment |
| 5 | **Consent Given** | 5  | DPDP Act 2023 consent flag captured |
| 6 | **Geographic Signal** | 5  | State/region field populated |
| 7 | **Response Time** | 5  | Reserved for Group E (Contact-to-Response speed) |

### 3.1 Source Quality Tiers

| Tier | Sources | Score Ratio |
|------|---------|-------------|
| Tier 1 (Excellent) | Referral, Walk-In | 100% of max weight |
| Tier 2 (Good) | Google Ads, Facebook | 75% of max weight |
| Tier 3 (Moderate) | IVR, WhatsApp | 60% of max weight |
| Tier 4 (Standard) | Website Organic, QR Code | 50% of max weight |
| Default (Lower) | Education Portal, Instagram, Event, CSV Import, API | 25% of max weight |

### 3.2 Engagement Level Breakdown

| Signal | Points Awarded |
|--------|---------------|
| Status has advanced beyond "New Enquiry" | 50% of engagement max weight |
| Counsellor assigned to the lead | 25% of engagement max weight |
| Activity log events (email opens, WhatsApp reads) | 25% reserved — activates in Group E |

---

## 4. Automated Workflows on Temperature Change

The system automatically triggers actions when a lead's temperature changes:

### 4.1 HOT Lead Alert 🔥

When a lead's temperature **becomes HOT** (score crosses the HOT threshold):

1. **In-app bell notification** sent to the assigned counsellor (visible in the notification bell icon)
2. **Email alert** sent to the assigned counsellor with:
   - Lead name, current score (out of 100)
   - Score breakdown bar
   - Direct "View Lead Profile" link
   - Urgency note to contact within 24 hours

> If no counsellor is assigned to the lead, the HOT alert is silently skipped. Assign a counsellor first.

### 4.2 COLD Lead Downgrade (Nurture Stub)

When a lead **downgrades to COLD** from WARM or HOT:

- A nurture sequence job is queued for Group F (Communication Engine) activation
- Currently a stub — the drip email/WhatsApp sequence will activate in Group F

---

## 5. Manual Score Override

Counsellors can manually override a lead's calculated score when they have direct knowledge about the lead's intent.

### 5.1 When to Use Manual Override

- You've spoken to the lead directly and they've expressed strong intent not captured in the system
- A referral came from a confirmed, high-quality source
- The automated score doesn't reflect offline engagement (in-person visit, event attendance)

### 5.2 How to Override a Score

1. Open the lead's profile page
2. Click the **"Scoring"** tab
3. Scroll to the **"Manual Override"** section
4. Click **"Override Score"** to expand the form
5. Enter the new score (0–100)
6. Enter a reason (minimum 10 characters — mandatory)
7. Click **"Apply Override"**

> Once overridden, the system's automatic recalculation is **paused** for that lead. The score will remain at the manually set value until the override is lifted.  
> A "Manually Set" badge appears next to the score on the lead card and scoring tab.

### 5.3 Override Permissions

| Role | Can Override |
|------|-------------|
| Senior Counsellor | Own leads only (leads assigned to them) |
| Junior Counsellor | Own leads only |
| Institution Admin | All leads |
| Super Admin | All leads |

### 5.4 Score Override History

Every override is permanently recorded in the **Score Override History** table (visible on the Scoring tab):

| Column | Description |
|--------|-------------|
| Previous Score | Score before the override |
| New Score | Manually set score |
| Reason | Counsellor's documented reason |
| Overridden By | Counsellor's name |
| Date | Timestamp |

Overrides are immutable — they cannot be deleted. This ensures a full audit trail.

---

## 6. Source Quality Report

The Source Quality Report shows which lead acquisition channels are delivering the highest quality leads, helping marketing teams allocate budget more effectively.

### 6.1 How to Access

The report is accessible from three locations:

1. **Standalone page:** Navigate to **CRM → Lead Scoring → Source Quality Report** in the sidebar
2. **Lead Index tab:** On the Leads list page, click the **"Source Quality"** tab
3. **Dashboard widget:** The **"Lead Quality by Source"** card on the CRM Dashboard shows the top 5 sources

### 6.2 Report Contents

| Column | Description |
|--------|-------------|
| Source Channel | The lead acquisition source |
| Avg. Score | Average lead score from this channel (0–100) |
| Total Leads | Total number of leads from this source |
| Converted | Number of leads that converted to enrolled students |
| Conversion Rate | Percentage converted |
| Quality Tier Badge | Visual tier indicator (Excellent / Good / Moderate / Standard / Lower) |

The report also includes:
- **Bar chart** — Average score by source (Chart.js)
- **Donut chart** — Lead volume distribution by source

### 6.3 Report Permissions

| Role | Access |
|------|--------|
| Senior Counsellor | ✅ |
| Junior Counsellor | ✅ |
| Admissions Director | ✅ |
| Institution Admin | ✅ |
| Super Admin | ✅ |
| Marketing Manager | ✅ |

---

## 7. Admin Configuration Guide

Institution Admins and Super Admins can customise the scoring engine weights and temperature thresholds.

**Access:** CRM → Lead Scoring → Scoring Configuration  
**Route:** `/crm/scoring/config`

### 7.1 Adjusting Signal Weights

Each of the 7 signal categories has a configurable weight between 0 and 30 points. Use the sliders to set the weight for each signal.

> The system does **not** enforce that weights total exactly 100. The score is capped at 100 regardless. If weights sum to more than 100, low-scoring leads benefit — adjust thoughtfully.

**Recommended configurations by institution type:**

| Institution Type | High-Weight Signals |
|-----------------|---------------------|
| Engineering/MBA (high competition) | Programme Interest, Source Quality |
| Vocational/Diploma (volume play) | Profile Completeness, Engagement |
| Premium Universities | Response Time (once Group E is live) |

### 7.2 Adjusting Temperature Thresholds

| Threshold | Default | Description |
|-----------|---------|-------------|
| HOT Threshold | 75 | Scores ≥ this value → HOT. Must be greater than WARM threshold. |
| WARM Threshold | 50 | Scores ≥ this value → WARM. Must be less than HOT threshold. |

> **Cross-field validation:** The system prevents saving a configuration where HOT threshold ≤ WARM threshold.

### 7.3 Live Score Preview

The configuration page includes a **Live Score Preview** panel (sticky sidebar):
- Adjust the sliders to see a sample score update in real-time
- The preview uses a hypothetical "average lead" profile to show approximate impact
- Use this to validate your configuration before saving

---

## 8. Viewing Score on a Lead Profile

The **Scoring** tab on any lead's profile page shows:

| Section | Contents |
|---------|---------|
| **Score Breakdown** | Grid showing each signal category and its contribution (pts earned / max pts) |
| **Score Bar** | Visual progress bar coloured by temperature (red=HOT, amber=WARM, blue=COLD) |
| **Override Badge** | "Manually Set" badge if score has been manually overridden |
| **Manual Override Form** | Expandable form (visible if you have override permission) |
| **Score Override History** | Immutable table of all past overrides |

---

## 9. Frequently Asked Questions

**Q: Why is my lead showing COLD even though I spoke to them?**  
A: The system only knows what's in the CRM. Update the lead's status (e.g. to "Counselling Scheduled"), ensure a programme interest is saved, and re-run the score. Alternatively, use a Manual Score Override with a documented reason.

**Q: The HOT alert email went to the wrong counsellor.**  
A: The alert goes to the lead's `Assigned Counsellor`. Check the lead's profile and update the assigned counsellor.

**Q: Can I turn off the automatic score recalculation?**  
A: Not globally. You can lock a single lead's score by using the Manual Score Override — once overridden, automatic recalculation is paused for that lead.

**Q: Why does a referral source score the same as a walk-in?**  
A: Both Referral and Walk-In are Tier 1 sources (highest quality tier). This reflects the BRD scoring model. If your institution considers them differently, adjust the weights accordingly — or raise a change request.

**Q: When will response time scoring be activated?**  
A: Response time scoring is reserved for Group E (Enquiry & Counselling Pipeline), which introduces the contact timeline. Once Group E is live, the 5-point response time weight will activate automatically.

---

## 10. BRD Traceability

| BRD Req ID | Feature | Status |
|------------|---------|--------|
| CRM-LQ-001 | Per-institution configurable rule-based scoring engine | ✅ |
| CRM-LQ-002 | 7 scoring parameters incl. demographics, course match, source, engagement | ✅ |
| CRM-LQ-003 | AI-enhanced scoring (Claude API) | ⏳ Phase 2 |
| CRM-LQ-004 | Score recalculated on every qualifying activity (web form, status change) | ✅ |
| CRM-LQ-005 | Per-institution configurable temperature thresholds with UI config page | ✅ |
| CRM-LQ-006 | Score threshold triggers automated workflow (HOT alert, COLD nurture) | ✅ |
| CRM-LQ-007 | Manual score override with mandatory reason + audit trail | ✅ |
| CRM-LQ-008 | Source quality report — avg score, volume, conversion rate by channel | ✅ |

---

## 11. Technical Reference

| Component | Location |
|-----------|---------|
| Scoring Algorithm | `app/Services/CRM/Scoring/LeadScoringService.php` |
| Score Recalculation Job | `app/Jobs/CRM/RecalculateLeadScoreJob.php` |
| Scoring Config Model | `app/Models/CRM/InstitutionScoringConfig.php` |
| Score Override Model | `app/Models/CRM/ScoreOverride.php` |
| HOT Alert Job | `app/Jobs/CRM/SendHotLeadAlertJob.php` |
| HOT Alert Notification | `app/Notifications/CRM/HotLeadAlertNotification.php` |
| HOT Alert Email Template | `resources/views/emails/crm/hot-lead-alert.blade.php` |
| Config Page | `resources/views/crm/scoring/config.blade.php` |
| Source Quality Report | `resources/views/crm/scoring/source-quality.blade.php` |
| Migration: scoring configs | `database/migrations/2026_04_12_000001_create_institution_scoring_configs_table.php` |
| Migration: score overrides | `database/migrations/2026_04_12_000002_create_score_overrides_table.php` |

---

*Last Updated: 9 April 2026 — Group D Implementation Complete*
