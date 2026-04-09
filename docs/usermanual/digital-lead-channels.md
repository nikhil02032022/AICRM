# A2A CRM — Digital Lead Channels & Bulk Import
## User Manual — Group C Sprint
**Version:** 1.0  
**Date:** April 2026  
**Module:** Lead Capture — Digital Channels (BRD: CRM-LC-003, LC-004, LC-008, LC-012, CRM-SA-010)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Who Can Use These Features](#2-who-can-use-these-features)
3. [Setting Up a Channel Integration](#3-setting-up-a-channel-integration)
   - 3.1 [Google Lead Form Extensions](#31-google-lead-form-extensions)
   - 3.2 [Meta (Facebook/Instagram) Lead Ads](#32-meta-facebookinstagram-lead-ads)
   - 3.3 [Education Portals (Shiksha, CollegeDekho, Careers360, Collegedunia)](#33-education-portals)
4. [Managing Integrations](#4-managing-integrations)
5. [Bulk CSV / Excel Lead Upload](#5-bulk-csv--excel-lead-upload)
   - 5.1 [Preparing Your File](#51-preparing-your-file)
   - 5.2 [Uploading the File](#52-uploading-the-file)
   - 5.3 [Monitoring Import Progress](#53-monitoring-import-progress)
   - 5.4 [Downloading the Error Report](#54-downloading-the-error-report)
6. [How Leads Flow In from Digital Channels](#6-how-leads-flow-in-from-digital-channels)
7. [Import History](#7-import-history)
8. [DPDP Compliance Notes](#8-dpdp-compliance-notes)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Overview

Group C connects A2A CRM to your external lead sources automatically, eliminating manual data entry from advertising platforms and portals.

| Feature | What it does |
|---|---|
| **Google Lead Form Extensions** | Receives leads directly from Google Ads the moment a prospect submits a Google lead form |
| **Meta Lead Ads** | Auto-imports leads from Facebook and Instagram ad campaigns via the Meta Graph API |
| **Education Portals** | Ingests leads from Shiksha, CollegeDekho, Careers360, and Collegedunia through signed webhooks |
| **Bulk CSV / Excel Upload** | Lets you upload a spreadsheet of up to ~5,000 leads at once with a validation error report |
| **Integration Credential Manager** | Secure, encrypted storage for all webhook secrets and API tokens — managed from Settings → Integrations |

All imported leads:
- Are automatically assigned the correct **Lead Source** (e.g. `Google Ads`, `Meta Lead Ads`, `Shiksha`)
- Trigger **duplicate detection** in the background (same email/mobile check)
- Appear in the **Leads** list immediately after the import job completes

---

## 2. Who Can Use These Features

### Integration Setup (Settings → Integrations)

| Role | View Integrations | Add / Edit / Remove |
|---|---|---|
| Super Admin | ✅ | ✅ |
| Institution Admin | ✅ | ✅ |
| Admissions Director | ✅ | ❌ |
| Admissions Manager | ✅ | ❌ |
| All other roles | ❌ | ❌ |

> Only Super Admins and Institution Admins can add or modify credential configurations.

### Bulk Import (Bulk Import page)

| Role | Access |
|---|---|
| Super Admin | ✅ |
| Institution Admin | ✅ |
| Admissions Director | ✅ |
| Admissions Manager | ✅ |
| Marketing Manager | ✅ |
| All other roles | ❌ |

---

## 3. Setting Up a Channel Integration

Before any digital channel can send leads to your CRM, you must create an **Integration** record. This stores the webhook URL and encrypted credentials for that channel.

**Navigate to:** Sidebar → **Integrations** (under Settings)

Click **Add Integration** (top-right button). You will see the *Add Channel Integration* form.

### 3.1 Google Lead Form Extensions

**Required credentials:** Webhook Secret

**Steps:**

1. In the CRM, select **Channel → Google Ads** and enter a **Label** (e.g. `MBA 2026 Admissions Campaign`).
2. Enter the **Webhook Secret** — this is a string you choose (e.g. a random 32-character token). Keep it safe.
3. Click **Save Integration**. The system generates a unique **Webhook URL** — displayed on the integration card.
4. In **Google Ads:**
   - Open your campaign → Lead Form Extension → **Webhook integration**.
   - Paste the **Webhook URL** from step 3.
   - Paste the same **Webhook Secret** you set in the CRM.
   - Click **Send test call** in Google Ads. If everything is configured correctly, a test lead will appear in your CRM Leads list within a few seconds.

> ℹ️ Google sends leads using `X-Goog-Signature` header. The CRM verifies this before processing.

---

### 3.2 Meta (Facebook/Instagram) Lead Ads

**Required credentials:** App Secret · Page Access Token · Verify Token

**Steps:**

1. In the CRM, select **Channel → Meta Lead Ads** and enter a **Label** (e.g. `Instagram Engineering Ads`).
2. Fill in:
   - **App Secret** — from your Meta App → Settings → Basic.
   - **Page Access Token** — generate a never-expiring token from Meta Business Suite (recommended) or use a long-lived token.
   - **Verify Token** — a string you choose for the initial Facebook webhook verification handshake.
3. Click **Save Integration**. Copy the generated **Webhook URL**.
4. In **Meta Business Manager / Developer Portal:**
   - Go to your App → Webhooks → Subscribe to `leadgen` events on your Facebook Page.
   - Paste the **Webhook URL** and the **Verify Token** you set in step 2.
   - Meta will call the URL with a `hub.challenge` verification request — the CRM handles this automatically and responds with the correct challenge.
5. Once verified, configure your Lead Ads to use this page. All new lead submissions will flow into the CRM automatically.

> ℹ️ The CRM acknowledges Meta's webhook delivery immediately (`HTTP 200`) and fetches full lead data in a background job using the Page Access Token. This prevents Meta from retrying incorrectly.

---

### 3.3 Education Portals

**Supported portals:** Shiksha · CollegeDekho · Careers360 · Collegedunia

**Required credentials:** Webhook Secret (per portal's naming convention)

Each portal has its own webhook push mechanism. The configuration steps are the same for all four:

1. In the CRM, select the appropriate **Channel** (e.g. `Shiksha`) and enter a **Label**.
2. Enter the **Webhook Secret** provided by the portal's partner representative.
3. Click **Save Integration**. Copy the generated **Webhook URL**.
4. Share the **Webhook URL** and **Secret** with your portal account manager so they can configure their system to push new enquiries to your CRM.

> ℹ️ Each portal sends a signature in the `X-Portal-Signature` header. The CRM validates this before creating any lead. Leads that fail signature verification are rejected automatically and logged.

---

## 4. Managing Integrations

**Navigate to:** Sidebar → **Integrations**

Each integration card shows:

| Item | Description |
|---|---|
| **Label** | Your descriptive name for this integration |
| **Channel** | Google Ads / Meta / Shiksha / etc. |
| **Status** | Active (green) or Inactive (grey) |
| **Last webhook received** | How recently leads came in via this channel — useful for diagnosing if flows have stopped |
| **Webhook URL** | One-click copy button to copy the URL |

### Editing an Integration

Click **Edit** on any card to update the label, credentials, or toggle it active/inactive.

> Setting an integration to **Inactive** stops the CRM from accepting webhooks on that URL immediately — useful when a campaign ends or credentials rotate.

### Removing an Integration

Click **Remove → Confirm**. This soft-deletes the credential record. Existing leads imported through it are not affected.

> ⚠️ Removing an integration invalidates its webhook URL. Ensure you have removed it from the advertising platform first to avoid failed deliveries on the platform's side.

---

## 5. Bulk CSV / Excel Lead Upload

Use this when you have a spreadsheet of leads from an offline event, referral list, old system export, or any source not covered by webhooks.

**Navigate to:** Sidebar → **Bulk Import** → **Upload CSV / Excel**

### 5.1 Preparing Your File

Download the template first: the upload page shows a **Download CSV template** link. Use it to ensure your columns are in the correct format.

| Column | Required | Notes |
|---|---|---|
| `first_name` | ✅ | |
| `last_name` | ✅ | |
| `email` | ✅ | Must be a valid email address |
| `mobile` | ✅ | 10-digit Indian mobile number |
| `programme_interest` | ✅ | Course / programme name |
| `city` | ❌ | |
| `state` | ❌ | |
| `notes` | ❌ | Any additional notes |

**File limits:**
- Format: `.csv` or `.xlsx` (Excel)
- Maximum size: **5 MB**
- Maximum rows per upload: **~5,000 leads**
- For larger datasets, split the file and upload in batches

### 5.2 Uploading the File

1. Go to **Bulk Import → Upload CSV / Excel**.
2. Select the **Source Channel** — choose the most appropriate option from the dropdown (e.g. `Education Fair`, `Referral`, `Other Offline`). This is recorded as the lead source for every row in the file.
3. Drag and drop your file into the upload area, or click **Browse** to select it from your computer. The file name will be displayed once selected.
4. Click **Upload & Import**.

The file is uploaded and the import is processed in the background — you will not need to wait on the page. You will receive an **email notification** when the import is complete.

### 5.3 Monitoring Import Progress

Go to **Bulk Import** (the main index page) to see all import batches — current and historical.

| Column | Meaning |
|---|---|
| **File / Batch** | File name and a short batch ID |
| **Channel** | Source channel selected at upload |
| **Status** | `Pending` → `Processing` → `Completed` / `Completed with Errors` / `Failed` |
| **Total** | Total rows in the uploaded file |
| **Success** | Rows that created a lead successfully |
| **Failed** | Rows that were rejected (duplicate, validation error, etc.) |
| **Initiated by** | The user who uploaded the file |
| **Date** | Upload timestamp |

Use the **Channel** and **Status** filters at the top to narrow the list.

### 5.4 Downloading the Error Report

If any rows failed, a **Download Error Report** button appears in the Actions column. Click it to download a `.csv` file containing:

- The original row data
- An `error` column explaining why each row was rejected (e.g. `Invalid email format`, `Duplicate mobile number`)

Fix the errors in the file and re-upload only the failed rows.

---

## 6. How Leads Flow In from Digital Channels

Understanding this flow helps diagnose problems:

```
Prospect submits Google / Meta / Portal form
          ↓
Platform sends webhook → /api/v1/crm/webhooks/{channel}/{uuid}
          ↓
CRM verifies HMAC signature (X-Goog-Signature / X-Hub-Signature-256 / X-Portal-Signature)
          ↓
CRM acknowledges with HTTP 200 immediately (< 3 seconds SLA)
          ↓
Background job dispatched to crm-imports queue
   → Fetches full lead data (Meta: Graph API call; others: webhook payload normalised)
   → Validates and maps fields via channel-specific normaliser
   → Creates Lead record with correct source and UTM data
   → Dispatches duplicate detection job
          ↓
Lead appears in CRM Leads list
```

**If a lead does not arrive:**
1. Check the Integration card → **Last webhook received** timestamp.
2. If it shows `No webhook received yet`, the platform is not sending to the correct URL — verify the Webhook URL in the platform settings.
3. If the timestamp is recent but no lead was created, the signature verification may have failed (mismatched secret) — re-check the credentials.

---

## 7. Import History

The **Bulk Import** page shows all imports — both manual CSV uploads and webhook-triggered batches. Webhook batches are grouped automatically (one batch per channel per day).

You can filter by:
- **Channel** — to see all leads from a specific source
- **Status** — to find failed or pending batches quickly

The history is retained indefinitely for audit and compliance purposes.

---

## 8. DPDP Compliance Notes

| Requirement | How the CRM handles it |
|---|---|
| **Consent at capture** | Webhook leads carry the consent given on the originating platform (Google / Meta / portal). The CRM records `consent_given = true` and timestamps the import. |
| **Data minimisation** | Only the fields mapped by the channel normaliser are stored — no extra raw payload data is persisted. |
| **No PII in logs** | Mobile numbers and email addresses are never written to application logs. Import errors reference row numbers, not personal data. |
| **Right to erasure** | Leads imported through these channels can be anonymised using the standard lead erasure workflow (same as any other lead). |

---

## 9. Troubleshooting

### Leads from Google Ads are not appearing

- Verify the Webhook URL in Google Ads matches exactly what is shown in the CRM Integration card.
- Verify the Webhook Secret set in Google Ads matches the one stored in the CRM integration.
- In Google Ads, use **Send test call** — if it fails, the URL or secret is wrong.
- Ensure the integration is set to **Active** in the CRM.

### Meta webhooks are not delivering

- Check the Meta Developer Portal → Webhooks to confirm the subscription is active and the page is subscribed to `leadgen` events.
- Verify the Verify Token matches.
- Check the Page Access Token has not expired (use a long-lived or system user token to avoid expiry).

### CSV upload fails immediately

- Ensure the file is `.csv` or `.xlsx` — `.xls` (older Excel format) is not supported.
- Check the file is under 5 MB.
- Ensure the header row matches the template exactly (column names are case-sensitive).

### Some rows failed in the error report

- Download the error report CSV.
- Fix the flagged rows (the `error` column explains each issue).
- Re-upload only the corrected rows — do not re-upload the entire file, as successfully imported rows will trigger duplicate checks.

### The Import Status is stuck on "Processing"

- The import runs in a background queue (`crm-imports`). If the queue worker is not running, batches will remain in `Pending`/`Processing`.
- Contact your system administrator to verify Laravel Horizon is running (`php artisan horizon`).

### I cannot see "Bulk Import" or "Integrations" in the sidebar

- These menu items are permission-gated. Contact your Institution Admin to verify your role has `crm.leads.import` (for Bulk Import) or `crm.integrations.view` (for Integrations) permissions assigned.
