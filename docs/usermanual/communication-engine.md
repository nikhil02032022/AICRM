# Communication Engine — User Manual

**Product:** A2A Educational CRM (Admissions-2-Alumni)  
**Company:** MEETCS Pvt. Ltd.  
**Module:** Group F — Communication Engine  
**BRD Reference:** CRM-LC-007 · CRM-LC-010 · CRM-CC-001 to CRM-CC-023  
**Last Updated:** April 2026  
**Audience:** Counsellors · Admissions Managers · Institution Admins · Marketing Teams

---

## Table of Contents

1. [Overview](#1-overview)  
2. [Who Can Use This Module](#2-who-can-use-this-module)  
3. [Email Communication](#3-email-communication)  
   - 3.1 Communication Templates  
   - 3.2 Sending an Email from a Lead Record  
   - 3.3 Email Campaigns (Bulk)  
   - 3.4 Email Delivery Tracking  
   - 3.5 Configuring a Sender Domain (Admin)  
   - 3.6 Unsubscribe Management (DPDP)  
4. [SMS Communication](#4-sms-communication)  
   - 4.1 DLT Template Registration  
   - 4.2 Sending an SMS from a Lead Record  
   - 4.3 SMS Campaigns (Bulk)  
   - 4.4 DNC and Opt-Out Management  
5. [WhatsApp Communication](#5-whatsapp-communication)  
   - 5.1 How WhatsApp Leads Are Auto-Created (LC-007)  
   - 5.2 WhatsApp Shared Inbox  
   - 5.3 Sending a Template Message  
   - 5.4 Conversation Threads  
   - 5.5 Bulk WhatsApp Broadcasts  
   - 5.6 Delivery & Read Status  
6. [Voice and IVR](#6-voice-and-ivr)  
   - 6.1 How IVR Leads Are Auto-Created (LC-010)  
   - 6.2 Click-to-Call  
   - 6.3 Call Dispositions and Logging  
   - 6.4 Call Recordings  
   - 6.5 IVR Configuration (Admin)  
7. [Unified Inbox](#7-unified-inbox)  
8. [Activity Timeline Integration](#8-activity-timeline-integration)  
9. [Admin Configuration Guide](#9-admin-configuration-guide)  
   - 9.1 Sender Domain Setup  
   - 9.2 SMS Gateway Configuration  
   - 9.3 WhatsApp BSP Configuration  
   - 9.4 Telephony Provider Configuration  
10. [DPDP Compliance — Communication Module](#10-dpdp-compliance--communication-module)  
11. [Frequently Asked Questions](#11-frequently-asked-questions)

---

## 1. Overview

The A2A-CRM Communication Engine provides a **single, unified hub** for every channel through which your institution engages prospective students — Email, SMS, WhatsApp, and Voice — all visible on the lead's activity timeline and accessible without leaving the CRM.

Key capabilities:

| Capability | Channels |
|------------|---------|
| **Individual messaging** from a lead record | Email · SMS · WhatsApp |
| **Bulk campaigns** to segmented lead lists | Email · SMS · WhatsApp |
| **Auto-lead creation** from inbound messages | WhatsApp (LC-007) · IVR (LC-010) |
| **Shared inbox** — all counsellors, one view | Email · WhatsApp |
| **Delivery tracking** — open, click, read, bounce | Email · WhatsApp |
| **DPDP-compliant** opt-out and DNC enforcement | All channels |
| **Activity timeline** — every interaction logged | All channels |

> **Important:** All communication jobs are processed asynchronously via Laravel Horizon queues. After you click "Send", the message may take a few seconds to dispatch — this is by design for reliability and performance.

---

## 2. Who Can Use This Module

| Role | Templates | Send 1:1 | Campaigns | Inbox | IVR Config | Sender Domain |
|------|:---------:|:--------:|:---------:|:-----:|:----------:|:-------------:|
| Institution Admin | ✅ Create/Edit | ✅ | ✅ | ✅ All | ✅ | ✅ |
| Admissions Manager | ✅ Create/Edit | ✅ | ✅ | ✅ Team | ❌ | ❌ |
| Counsellor | ✅ View only | ✅ (own leads) | ❌ | ✅ Own | ❌ | ❌ |
| Telecaller | ❌ | ✅ SMS only | ❌ | ✅ Own | ❌ | ❌ |
| Marketing Staff | ✅ Create/Edit | ✅ | ✅ | ❌ | ❌ | ❌ |

> **Permissions needed:**  
> - `crm.communication.templates.manage` — create/edit templates  
> - `crm.campaigns.send` — dispatch bulk campaigns  
> - `crm.inbox.all` — view all counsellors' inbound messages (managers only)  
> - `crm.settings.ivr` — manage IVR configuration  
> - `crm.settings.sender-domains` — manage sender domains

---

## 3. Email Communication

### 3.1 Communication Templates

Templates allow you to create reusable email content with personalisation merge tags that are automatically replaced with each lead's actual data when sent.

**Accessing Templates:**  
Go to **CRM → Communication → Templates**

**Creating an Email Template:**

1. Click **+ New Template** (top right).
2. Set **Template Name** (internal label — not visible to leads).
3. Set **Channel** → Email.
4. Set **Type** (Transactional / Marketing / Notification).
5. Enter the **Email Subject** with optional merge tags (e.g., `Welcome, {{first_name}}!`).
6. Use the HTML editor to build your email body.
7. Click **Save Template**.

**Available Merge Tags:**

| Tag | Replaced with |
|-----|--------------|
| `{{first_name}}` | Lead's first name |
| `{{full_name}}` | Lead's full name |
| `{{programme}}` | Programme of Interest |
| `{{counsellor_name}}` | Assigned counsellor's name |
| `{{institution_name}}` | Institution name |
| `{{booking_link}}` | Counselling appointment booking URL |
| `{{application_url}}` | Application form deep link |
| `{{unsubscribe_link}}` | DPDP-compliant unsubscribe URL (required for marketing emails) |

> **DPDP Requirement:** All marketing/promotional emails **must** include `{{unsubscribe_link}}` in the template body. The system will warn you before sending if this tag is absent from a Marketing-type template.

---

### 3.2 Sending an Email from a Lead Record

1. Open any lead: **CRM → Leads → [Lead Name]**
2. Click the **Send Email** button in the lead sidebar (or in the Communication tab).
3. Select a **template** from the dropdown (or compose custom).
4. Review the rendered preview — merge tags are pre-filled with the lead's data.
5. Click **Send Email**.

The email dispatches asynchronously. Within seconds, a new entry appears on the lead's **Activity Timeline** with type **Email Sent**.

> **Tip:** If the lead has `email_unsubscribed_at` set, the Send Email button will be disabled with an "Unsubscribed" badge. You cannot send marketing emails to an unsubscribed lead.

---

### 3.3 Email Campaigns (Bulk)

**Accessing Campaigns:**  
Go to **CRM → Communication → Email Campaigns**

**Creating a Campaign:**

1. Click **+ New Campaign**.
2. Enter **Campaign Name** and the **From Name / From Email** (must match a verified sender domain).
3. Select an **Email Template**.
4. Define the **Recipient Segment** — filter leads by status, source, programme, temperature, date range, etc.
5. Choose **Send Immediately** or **Schedule for Later** (pick date/time).
6. Click **Review Campaign** — the system shows you recipient count and a preview.
7. Click **Launch Campaign** to confirm.

The system fans out one `SendBulkEmailJob` per recipient on the `crm-comms-email` queue. Unsubscribed leads are automatically excluded.

**Live Campaign Stats (during send):**

| Metric | Meaning |
|--------|---------|
| Total Recipients | Total leads in segment at dispatch time |
| Sent | Emails dispatched to gateway |
| Delivered | Confirmed delivery by gateway |
| Opened | Recipient opened the email (pixel tracking) |
| Clicked | Recipient clicked any link |
| Bounced | Hard or soft bounce reported by gateway |
| Unsubscribed | Leads who unsubscribed via this campaign |

---

### 3.4 Email Delivery Tracking

All email events (delivered, opened, clicked, bounced, unsubscribed) are received via webhooks from your email provider (Mailgun / Postmark / AWS SES) and:

1. Updated on the `CommunicationLog` record (timestamps set).
2. Propagated to the lead's **score** (email opens/clicks increase engagement score via `RecalculateLeadScoreJob`).
3. Visible on the lead's **Activity Timeline**.

Hard bounces (invalid email address) automatically increment `email_bounce_count` on the lead record. After 3 hard bounces, the lead's email is flagged as invalid.

---

### 3.5 Configuring a Sender Domain (Admin)

Before sending emails, an admin must verify at least one sender domain.  
Go to **CRM → Settings → Sender Domains**

1. Click **+ Add Domain**.
2. Enter the domain (e.g., `admissions.gim.ac.in`).
3. Select your **Email Provider** (Mailgun, Postmark, AWS SES, SendGrid).
4. The system generates **SPF**, **DKIM**, and **DMARC** DNS records for you to add at your DNS registrar.
5. Once DNS propagates (typically 15–60 minutes), click **Verify DNS** — the system checks all three records.
6. When all three show ✅, the domain is verified and available for campaigns.

> **Security:** Email provider credentials (API keys) are stored in the **Integration Credentials** vault (AES-256 encrypted). Never enter them anywhere else in the system.

---

### 3.6 Unsubscribe Management (DPDP)

All unsubscribe events are handled automatically:

- **Via unsubscribe link** in email → webhook fires → `EnforceUnsubscribeJob` sets `email_unsubscribed_at` within 24 hours (DPDP Act, Section 6).
- **Manual opt-out** by counsellor: Open lead → **Communication** tab → click **Mark Email Opt-Out** → confirm.
- Once unsubscribed, the lead cannot receive marketing/campaign emails. Transactional emails (e.g., application confirmation, offer letter) may still send per admin configuration.
- Unsubscribe operations are **idempotent** — unsubscribing a lead that is already unsubscribed does nothing harmful.

---

## 4. SMS Communication

### 4.1 DLT Template Registration

TRAI mandates that all promotional and transactional SMS messages sent from Indian sender IDs must match a registered DLT (Distributed Ledger Technology) template. A2A-CRM manages this workflow.

**Accessing DLT Templates:**  
Go to **CRM → Communication → SMS → DLT Templates**

**Registering a DLT Template:**

1. Click **+ New DLT Template**.
2. Enter the **Template Name** (internal label).
3. Select the **Gateway** (MSG91, Textlocal, or Kaleyra).
4. Enter your **Sender ID** (6-character registered DLT sender, e.g., `ACCADM`).
5. Select **Message Type** (Transactional / Promotional / OTP / Service).
6. Enter the **Template Body** exactly as approved (or to be submitted) at TRAI's DLT portal, using `{#var#}` for variables, e.g.:  
   `Dear {#var#}, your application for {#var#} has been received. - ACCADM`
7. Click **Save as Draft**.
8. Once you have submitted the template at your gateway's DLT portal and received approval, return here and click **Mark as Approved**, then enter the **DLT Template ID** provided by your gateway.

> **Important:** The system will not send any SMS using a DLT template with status **DRAFT** or **PENDING_APPROVAL**. Only **APPROVED** templates can be used. This protects your institution from TRAI compliance violations.

---

### 4.2 Sending an SMS from a Lead Record

1. Open a lead record.
2. Click **Send SMS** in the sidebar.
3. Select an **Approved DLT Template**.
4. The message body preview shows with lead data substituted for variables.
5. Click **Send SMS**.

The SMS dispatches asynchronously. An **SMS Sent** entry appears on the lead's Activity Timeline.

> **Note:** If the lead has `sms_unsubscribed_at` set or `dnc_at` set, the Send SMS button is disabled.

---

### 4.3 SMS Campaigns (Bulk)

Go to **CRM → Communication → SMS → Campaigns**

1. Click **+ New SMS Campaign**.
2. Select an **Approved DLT Template** and **Gateway**.
3. Define the **Recipient Segment** (same filters as email campaigns).
4. Choose immediate or scheduled dispatch.
5. Click **Launch Campaign**.

Each recipient is sent via an individual `SendBulkSmsJob`. Leads with `sms_unsubscribed_at` or `dnc_at` are automatically excluded.

---

### 4.4 DNC and Opt-Out Management

| Flag | Set when | Effect |
|------|----------|--------|
| `sms_unsubscribed_at` | Lead replies STOP or counsellor marks opt-out | Blocked from SMS campaigns |
| `dnc_at` | Counsellor marks DNC or inbound DNC request | Blocked from ALL channels |

To manually mark a lead as DNC:  
Open lead → **Communication** tab → **Mark Do Not Contact** → confirm reason.

DNC operations take effect immediately within the CRM and within 24 hours via queue processing for any in-flight campaigns.

---

## 5. WhatsApp Communication

### 5.1 How WhatsApp Leads Are Auto-Created (BRD LC-007)

When a prospective student uses a **WhatsApp Click-to-Chat link** published by your institution (e.g., on your website, brochure, or social ads), and initiates a conversation with your institution's WhatsApp Business number, the CRM automatically:

1. Receives the inbound message webhook from your WhatsApp BSP.
2. Checks if the sender's phone number matches an existing lead (institution-scoped).
   - **Match found** → message is attached to the existing lead's conversation.
   - **No match** → a new lead is auto-created with:
     - **Source:** WhatsApp
     - **Status:** New Enquiry
     - **Consent Given:** `false` _(DPDP: WhatsApp initiation alone does not constitute consent — see section 10)_
     - A `DetectLeadDuplicatesJob` is dispatched automatically.
3. The configured **Welcome Template Message** is sent to the student.
4. The conversation appears in the counsellor's **WhatsApp Shared Inbox**.
5. The assigned counsellor is notified.

> **Counsellor Action Required:** When you first view an auto-created WhatsApp lead, review the lead's details and — if the student has verbally or in writing consented to receive communications — check the **Consent Confirmed** checkbox on the lead record to ensure DPDP compliance.

---

### 5.2 WhatsApp Shared Inbox

Go to **CRM → Communication → WhatsApp**

The shared inbox shows all WhatsApp conversations for leads assigned to you (counsellors) or your entire team (managers). Each row shows:

| Column | Meaning |
|--------|---------|
| Contact | Student name (or phone if not yet matched to lead) |
| Last Message | Preview of last message |
| Time | Time of last message |
| Status | OPEN · PENDING · RESOLVED |
| Bot | Whether chatbot is currently handling |
| Assigned | Which counsellor owns this conversation |

**Filtering the Inbox:**  
Use the tabs — **All** · **Open** · **Pending** · **Resolved** — or search by lead name/phone.

---

### 5.3 Sending a Template Message

WhatsApp Business rules require that the **first outbound message** to a lead (or after a 24-hour inactivity window) must use an approved Meta-registered [Message Template]. Free-form messages can only be sent within an active 24-hour conversation window.

To send a template message:

1. Open any lead record → **Communication** tab → **WhatsApp**.
2. Click **Send Template Message**.
3. Select a registered template (e.g., `application_received`, `offer_letter_ready`, `fee_reminder`).
4. Fill in the variable values if the template has parameters.
5. Click **Send**.

Available system templates include:

| Template Name | Use Case | Variables |
|--------------|----------|-----------|
| `application_received` | Confirm application receipt | `{{name}}`, `{{programme}}` |
| `offer_letter_ready` | Notify offer letter issued | `{{name}}`, `{{download_link}}` |
| `fee_reminder` | Payment due reminder | `{{name}}`, `{{amount}}`, `{{due_date}}` |
| `counselling_appointment` | Appointment confirmation | `{{name}}`, `{{date}}`, `{{time}}`, `{{counsellor}}` |
| `document_pending` | Document submission reminder | `{{name}}`, `{{doc_list}}` |

> **Note:** Custom templates must be submitted to Meta via your BSP and approved before use. See your admin for template addition.

---

### 5.4 Conversation Threads

Click any conversation in the shared inbox to open the full **thread view**:

- **Inbound** messages appear on the left (blue bubble).
- **Outbound** messages appear on the right (green bubble).
- Template messages show a Template badge.
- Media messages (images, documents) show a thumbnail or file icon.

**Sending a free-form reply** (within 24h window):  
Type in the message box at the bottom → press **Enter** or click **Send**.

**Resolving a conversation:**  
Click **Mark Resolved** (top right) when the enquiry is handled. Resolved conversations move to the Resolved tab and stop appearing in the active inbox.

---

### 5.5 Bulk WhatsApp Broadcasts

> **Important:** Bulk WhatsApp messages **must** use approved Message Templates. Free-form bulk messaging is not permitted under WhatsApp Business API policy.

Go to **CRM → Communication → WhatsApp → Broadcasts**

1. Click **+ New Broadcast**.
2. Select an **Approved Template**.
3. Define the **Recipient Segment** (leads filtered by status, programme, temperature, etc.).
4. Schedule or send immediately.
5. Click **Launch Broadcast**.

Each recipient is dispatched via an individual `SendBulkWhatsAppJob` on the `crm-comms-whatsapp` queue. The system respects BSP rate limits automatically.

---

### 5.6 Delivery & Read Status

The BSP sends delivery and read webhooks back to the CRM. You will see status icons on every sent message:

| Icon | Status |
|------|--------|
| ✓ | Sent to BSP |
| ✓✓ | Delivered to device |
| ✓✓ (blue) | Read by recipient |
| ✗ | Failed/rejected |

---

## 6. Voice and IVR

### 6.1 How IVR Leads Are Auto-Created (BRD LC-010)

When a prospective student calls your institution's **virtual (DID) number** configured in the IVR module, the CRM automatically:

1. Receives a webhook from your telephony provider (Exotel / Ozonetel / Knowlarity).
2. Checks if the caller's number matches an existing lead.
   - **Match found** → a Call Log is created and linked to the existing lead.
   - **No match** → a new lead is auto-created with:
     - **Source:** IVR
     - **Status:** New Enquiry
     - **Consent Given:** `false` _(verbal consent must be confirmed by counsellor after call)_
     - A Call Log is created with direction = INBOUND.
     - `DetectLeadDuplicatesJob` is dispatched.
3. The **fallback counsellor** configured in IVR Settings is notified.
4. A Call Logged entry appears on the lead's Activity Timeline.

> **After the call:** The counsellor receiving the IVR notification should open the lead, review the call log, confirm verbal consent if obtained, and record a disposition.

---

### 6.2 Click-to-Call

Counsellors can initiate an outbound call to any lead directly from the CRM without dialling manually.

**From a lead record:**

1. Open the lead.
2. Click the **📞 Call** button next to the lead's phone number.
3. The system initiates the call through your configured telephony provider.
4. Your desk phone or softphone rings first — pick up.
5. The system then connects you to the lead's number.
6. A **Call Log** is created automatically with direction = OUTBOUND, status = IN_PROGRESS.

---

### 6.3 Call Dispositions and Logging

After a call ends, the telephony provider webhook fires and the call log is updated with `duration_seconds` and `status = COMPLETED`. The system then prompts you to record a **disposition**:

| Disposition | When to Use |
|-------------|-------------|
| Interested | Lead expressed clear interest |
| Not Interested | Lead explicitly declined |
| Call Back | Lead requested to be called back; creates a follow-up task |
| Wrong Number | Wrong person answered |
| Not Reachable | No answer / switched off |
| Number Invalid | Number does not exist |
| Voicemail | Call went to voicemail |
| Busy | Lead was busy; follow up |

You can add optional **Disposition Notes** for context.

> **Auto-task creation:** If disposition is **Call Back**, the system automatically creates a **Follow-up Call** task due at the requested callback time.

---

### 6.4 Call Recordings

If your institution has configured call recording with your telephony provider AND the lead has explicitly given **call recording consent** (`call_consent_given = true`):

- The recording is automatically retrieved after the call ends.
- It is stored securely on S3 (Mumbai, ap-south-1, encrypted at rest).
- A **Play Recording** button appears on the Call Log in the lead's timeline.
- Only users with the `crm.calls.recordings.listen` permission can play recordings.

> **DPDP Requirement:** Call recording is **only** activated when `call_consent_given = true`. If consent has not been recorded, the recording URL is never fetched or stored, even if the provider made a recording. See Section 10 for how to record consent.

---

### 6.5 IVR Configuration (Admin)

Go to **CRM → Settings → IVR Configuration**

| Setting | Description |
|---------|-------------|
| Telephony Provider | Exotel / Ozonetel / Knowlarity |
| Virtual Number | The DID number prospective students call |
| Welcome Message | Text-to-speech or audio URL played to callers |
| Collect Name | Whether IVR prompts for caller's name |
| Collect Programme | Whether IVR prompts for programme interest |
| Fallback Counsellor | Who gets notified when an IVR lead is auto-created |
| Integration Credentials | Link to the provider's API credentials in the vault |

> The virtual number field is encrypted. It is never displayed in logs or exported reports.

---

## 7. Unified Inbox

Go to **CRM → Inbox**

The Unified Inbox consolidates **all inbound messages** across Email, WhatsApp, and SMS into a single chronological view — no more switching between tabs or checking multiple tools.

**Tabs:**

| Tab | Shows |
|-----|-------|
| All | All inbound messages across all channels |
| Email | Inbound emails to your institution domain |
| WhatsApp | Inbound WhatsApp messages |
| SMS | Inbound SMS replies (gateway-supported) |
| Unread | Unread messages only |

**Unread Badges:**  
The sidebar navigation shows unread counts per channel, updated every 10 seconds (Livewire polling).

**Actions from Unified Inbox:**

- Click any message row to open the full lead record and conversation.
- Click **Mark Read** to clear the unread indicator without opening.
- Click **Assign** to reassign the conversation to another counsellor.

**Manager View:**  
Managers with `crm.inbox.all` permission see the full team's inbox. A counsellor filter dropdown narrows the view to a specific team member.

---

## 8. Activity Timeline Integration

Every communication action — sent or received — is automatically logged to the lead's **Activity Timeline** (Group E feature). No manual logging is needed.

| Activity Type | Logged when |
|--------------|-------------|
| Email Sent | Outbound email dispatched to gateway |
| Email Opened | Open webhook received from provider |
| SMS Sent | SMS dispatched via gateway |
| WhatsApp Sent | Outbound WhatsApp message sent |
| WhatsApp Received | Inbound WhatsApp message received |
| Call Logged | Call completed and disposition recorded |

These entries are **read-only** (system-generated) and cannot be deleted or edited. They form the immutable audit trail for each lead's communication history.

---

## 9. Admin Configuration Guide

### 9.1 Sender Domain Setup

See [Section 3.5 — Configuring a Sender Domain](#35-configuring-a-sender-domain-admin) above.

**DNS Records Required:**

| Record Type | Purpose | Example value |
|-------------|---------|---------------|
| TXT | SPF | `v=spf1 include:mailgun.org ~all` |
| TXT | DKIM | `v=DKIM1; k=rsa; p=<public_key>` |
| TXT | DMARC | `v=DMARC1; p=quarantine; rua=mailto:dmarc@domain.com` |

All three must be verified before the domain can be used for campaigns.

---

### 9.2 SMS Gateway Configuration

Go to **CRM → Settings → Integrations** → select MSG91 / Textlocal / Kaleyra.

1. Click **+ Add Credential**.
2. Select the integration type (e.g., `MSG91 SMS`).
3. Enter your API Key and Sender ID.
4. Click **Save** — credentials are AES-256 encrypted before storage.
5. Click **Test Connection** to verify.

The system supports multiple SMS gateways simultaneously. The gateway used per campaign is selected when creating the campaign (or inherited from the DLT template's gateway).

---

### 9.3 WhatsApp BSP Configuration

Go to **CRM → Settings → Integrations** → select your BSP (Meta Cloud API / Interakt / Gupshup).

1. Add the BSP credentials (Phone Number ID, Business Account ID, API Token).
2. Set the **Webhook Verify Token** — the same token you configure in the Meta Business dashboard.
3. Register the webhook URL with Meta:  
   `https://your-crm.domain.com/api/v1/crm/webhooks/whatsapp`
4. Configure the **Welcome Template** — the first message auto-sent to new inbound WA leads.
5. Click **Activate BSP** — the system verifies connectivity.

---

### 9.4 Telephony Provider Configuration

Go to **CRM → Settings → IVR Configuration** (see [Section 6.5](#65-ivr-configuration-admin)).

For Exotel:
1. Enter your Exotel API Key + API Token.
2. Register the A2A-CRM webhook URL with Exotel:  
   `https://your-crm.domain.com/api/v1/crm/webhooks/telephony/exotel`
3. Configure the IP allowlist in Exotel dashboard to your A2A-CRM server's egress IP.

For Ozonetel and Knowlarity: similar — provider-specific setup instructions available in the Help Centre.

---

## 10. DPDP Compliance — Communication Module

The A2A-CRM Communication Engine is designed for full compliance with the **Digital Personal Data Protection (DPDP) Act, 2023**. This section summarises the compliance behaviours you and your team must be aware of.

### 10.1 Consent at the Point of Communication

| Channel | When consent is confirmed |
|---------|--------------------------|
| Email | At lead creation via web form (explicit checkbox) |
| SMS | At lead creation via web form |
| WhatsApp (auto-created lead) | **Counsellor must confirm** — `consent_given` starts as `false` for WA auto-leads |
| IVR (auto-created lead) | **Counsellor must confirm** after verbal consent during call |

To confirm consent for a WhatsApp or IVR lead:
1. Open the lead record.
2. On the **Details** tab, check **Consent Confirmed** with a note (e.g., "Verbal consent given during call on 10 Apr 2026").
3. Save — `consent_given` is set to `true` and logged with timestamp in `consent_records`.

### 10.2 Right to Opt-Out

| Opt-out type | How it's triggered | Takes effect |
|--------------|--------------------|--------------|
| Email unsubscribe | Click unsubscribe link in email | Within 24h via queue |
| SMS opt-out | Reply STOP · or counsellor action | Within 24h via queue |
| WhatsApp opt-out | Counsellor action on lead record | Immediately |
| Do Not Contact (DNC) | Counsellor marks DNC on lead | Immediately across all channels |

Opt-out operations are **idempotent** — repeating them does not cause errors or duplicates.

### 10.3 Call Recording Consent

Call recording is strictly gated:
- `call_consent_given` must be `true` before any recording is stored.
- If consent is `false`, the recording is never retrieved from the provider, even if the provider recorded the call.
- To set call consent: Open lead → **Communication** tab → **Call Consent** toggle → confirm.
- This toggle is logged in `audit_logs` with the user, timestamp, and IP.

### 10.4 No PII in Logs

The system automatically scrubs all Personally Identifiable Information (names, phone numbers, email addresses, Aadhaar numbers) from application logs using the `PiiScrubber` log processor. If you see any PII in system logs, report it to the IT admin immediately — it is a compliance violation.

### 10.5 Data Storage

All communication-related personal data (phone numbers within `WhatsAppMessage`, `CallLog`; email content) is stored:
- On servers in **AWS ap-south-1 (Mumbai)** only.
- Phone numbers in `whatsapp_conversations.wa_phone_number`, `call_logs.from_number`, `call_logs.to_number` are **encrypted at rest** using `Crypt::encryptString()`.
- S3 recordings are encrypted at rest (AES-256) with server-side encryption.

---

## 11. Frequently Asked Questions

**Q: Why does the Send Email button not appear on some leads?**  
A: The lead either has `email_unsubscribed_at` set (they opted out) or `dnc_at` set (Do Not Contact). Check the lead's Communication tab for the exact flag.

**Q: How quickly are bulk emails sent?**  
A: Bulk campaigns are processed via the `crm-comms-email` queue with up to 10 parallel workers. A 1,000-lead campaign typically completes within 2–5 minutes depending on your email provider's rate limits.

**Q: A WhatsApp lead was created but the consent field shows "Not Confirmed" — what should I do?**  
A: This is correct and expected. WhatsApp auto-created leads start with `consent_given = false` (DPDP requirement). Once you've confirmed in conversation that the student consents to communications, check the **Consent Confirmed** checkbox on the lead record.

**Q: Can I send a free-form WhatsApp message to a new lead?**  
A: Not immediately. WhatsApp Business API requires the first message in a conversation (or after 24 hours of inactivity) to be an approved template. Send a template message first. Once the student replies, a 24-hour free-form window opens and you can send custom messages.

**Q: Why do I need DLT template approval before sending SMS?**  
A: TRAI mandates that all SMS messages must match a TRAI-registered DLT template. The system enforces this to protect your institution from regulatory violations. Submit your templates at your gateway's DLT portal and mark them as approved in the CRM once you receive the DLT Template ID.

**Q: Can I listen to all call recordings?**  
A: Only users with the `crm.calls.recordings.listen` permission can play recordings, and only for calls that had `call_consent_given = true`. Recordings are never accessible for calls where consent was not confirmed.

**Q: Why is the SMS Opt-Out taking up to 24 hours?**  
A: The DPDP Act specifies that opt-out requests must be actioned within 24 hours. The system dispatches the `EnforceUnsubscribeJob` immediately, but the rule allows up to 24 hours for full propagation across all queued campaigns.

**Q: What happens to a campaign that is in-progress when a lead opts out?**  
A: The `SendBulkEmailJob` and `SendBulkSmsJob` check the lead's unsubscribe/DNC status immediately before dispatch (not just at campaign creation). If the flag was set after the campaign launched but before the job for that lead runs, the message is skipped for that lead.

**Q: I accidentally clicked "Launch Campaign" — can I cancel it?**  
A: If the campaign status is still `SENDING`, go to **Campaigns → [Campaign Name] → Pause Campaign**. This stops new jobs from dispatching. Messages already in the queue may still send. For scheduled campaigns not yet started, you can cancel fully.

**Q: The IVR webhook is not creating leads — what should I check?**  
A: Verify: (1) the IVR Credentials are active in Integration Settings, (2) the webhook URL is correctly registered with your provider, (3) the provider's IP is on the allowlist, and (4) the `IvrConfig.is_active` is `true` for your campus. Check the Laravel Horizon dashboard for failed `ProcessIvrLeadCreationJob` jobs and review error messages.
