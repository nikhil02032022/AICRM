# A2A CRM — Web Enquiry Forms
## User Manual — Group B Sprint
**Version:** 1.0  
**Date:** April 2026  
**Module:** Lead Capture — Web Forms (BRD: CRM-LC-001, LC-002, LC-009, LC-015)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Who Can Use Web Forms](#2-who-can-use-web-forms)
3. [Creating a Web Form](#3-creating-a-web-form)
4. [Form Fields & Conditional Logic](#4-form-fields--conditional-logic)
5. [Sharing a Form — Public URL](#5-sharing-a-form--public-url)
6. [Embed on Your Website (iFrame)](#6-embed-on-your-website-iframe)
7. [QR Code for Events & Walk-ins](#7-qr-code-for-events--walk-ins)
8. [UTM Tracking](#8-utm-tracking)
9. [Editing & Deactivating a Form](#9-editing--deactivating-a-form)
10. [How Leads Flow In](#10-how-leads-flow-in)
11. [DPDP Consent & Privacy](#11-dpdp-consent--privacy)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. Overview

Web Forms let your institution create branded, shareable enquiry forms that prospective students can fill out — no login required. Each form submission automatially creates a **Lead** in the CRM, with the source, UTM tracking, and consent recorded.

**What you can do with Web Forms:**
- Build a form in minutes with an easy drag-and-drop-style field builder
- Embed it on your institution's website (iFrame) or share a direct link
- Generate a QR code for events, walk-in desks, and printed materials
- Auto-capture UTM parameters from marketing campaigns
- Collect DPDP-compliant consent on every submission

---

## 2. Who Can Use Web Forms

| Role | Can View | Can Create | Can Edit | Can Delete |
|------|----------|------------|----------|------------|
| Institution Admin | ✅ | ✅ | ✅ | ✅ |
| Admissions Manager | ✅ | ✅ | ✅ | ✅ |
| Super Admin | ✅ | ✅ | ✅ | ✅ |
| Admissions Director | ✅ | ❌ | ❌ | ❌ |
| Senior / Junior Counsellor | ✅ | ❌ | ❌ | ❌ |

> Counsellors can view a form's public URL and QR code but cannot create or modify forms.

---

## 3. Creating a Web Form

1. **Navigate** to **CRM → Web Forms** in the left sidebar.
2. Click **New Form** (top-right button).
3. Fill in the **Form Settings**:

| Field | Description | Example |
|-------|-------------|---------|
| **Form Name** | Internal label visible only to staff | `MBA 2026 Walk-in Enquiry` |
| **URL Slug** | Auto-generated from name — appears in the public link `/f/{slug}` | `mba-2026-walk-in` |
| **Lead Source** | Pre-sets the `source` field on every lead captured by this form | `Event` |
| **Thank-you Redirect URL** | Where to send the student after submission (optional) | `https://youruniversity.edu/thank-you` |
| **Consent Form Version** | Version string stored with every submission for DPDP audit | `v1.0` |
| **Accent Colour** | Brand colour applied to the public form's submit button | `#6366f1` |
| **Activate immediately** | If checked, the form goes live instantly | ✅ |

4. Add **custom fields** using the **Add Field** button (see §4 below).
5. Click **Create Form**.
6. You are taken to the **Embed Code & QR** page.

---

## 4. Form Fields & Conditional Logic

### Default Fields (always on every form)
Every form automatically includes these core fields — you do not need to add them:
- **First Name** (required)
- **Mobile Number** (required, Indian format +91)
- **Last Name** (required)
- **Email** (optional)
- **Consent checkbox** (required — DPDP mandatory)

### Custom Fields
Click **Add Field** to add any of the following field types:

| Type | When to use | Example |
|------|-------------|---------|
| `text` | Short free-text answer | City |
| `tel` | Phone / alternate number | Alternate mobile |
| `email` | Secondary email | Parent's email |
| `select` | Dropdown — pick from list | Programme of Interest |
| `textarea` | Multi-line answer | Notes / Message |
| `checkbox` | Yes/No question | Currently studying? |
| `hidden` | Pre-fill values invisibly from URL params | Campaign ID |

### Conditional Logic (Show/Hide Fields)

You can make a field **appear only when a condition is met** using the **Add conditional logic** toggle per field.

**Example:** Show "Specialisation" only when "Programme of Interest" is "MBA":

| Setting | Value |
|---------|-------|
| When field ID | `programme_interest` |
| Operator | `equals` |
| Value | `MBA` |

Supported operators: `equals`, `not equals`, `contains`

The condition is evaluated in real-time in the student's browser using Alpine.js — hidden fields are never submitted.

---

## 5. Sharing a Form — Public URL

Every form gets its own public URL:

```
https://yourcrm.domain/f/{slug}
```

Example: `/f/mba-2026-walk-in`

**To find the URL:**
- Go to **Web Forms** list → click **Embed** action on any form row.
- The Public URL is shown with a one-click **Copy** button.

> ⚠️ The URL works without any login. Keep it published only on official institution channels.

---

## 6. Embed on Your Website (iFrame)

1. Go to **Web Forms → [Form Name] → Embed Code**.
2. Under **iFrame Embed Code**, click **Copy** on the code snippet:

```html
<iframe src="https://yourcrm.domain/f/mba-2026-walk-in/embed"
        width="100%" height="600" frameborder="0"
        allow="clipboard-write" style="border:none;"></iframe>
```

3. Paste this code into any HTML page on your institution website.

**Notes:**
- The embed URL (`/embed`) renders the form without the CRM's navigation, sidebar, or header — it's a clean card that fits inside an iFrame naturally.
- The form is responsive and works on mobile browsers.
- Consent is still collected on the embedded form.

---

## 7. QR Code for Events & Walk-ins

The QR code links to the form with UTM parameters pre-set:

```
/f/{slug}?utm_source=qr&utm_medium=event&utm_campaign={slug}
```

**Using the QR code:**
1. Go to **Embed Code & QR** for your form.
2. The QR image is displayed on screen.
3. Click **Download PNG** to save it.
4. Print and display at:
   - Reception / walk-in desks
   - Event banners and roll-ups
   - Brochures and flyers
   - Digital screens

When a student scans the QR code:
- The form opens on their phone
- `source = qr_code` is automatically set on the lead
- UTM parameters are captured by Alpine.js and stored on the lead record

> ℹ️ The QR code image can only be downloaded by logged-in staff. The public form it points to needs no login.

---

## 8. UTM Tracking

UTM parameters in the URL are automatically captured when the form loads and saved with the lead record in the `source_utm_params` JSON field.

**Supported parameters:**

| Parameter | Description |
|-----------|-------------|
| `utm_source` | Traffic source (e.g. `qr`, `google`, `facebook`) |
| `utm_medium` | Channel (e.g. `event`, `cpc`, `social`) |
| `utm_campaign` | Campaign name (e.g. `open-day-2026`) |
| `utm_term` | Ad keyword |
| `utm_content` | Ad variant / creative |

**Example URL with UTM:**
```
/f/mba-2026-walk-in?utm_source=facebook&utm_medium=paid&utm_campaign=mba-admissions
```

These values appear on the lead's detail page and in analytics reports.

---

## 9. Editing & Deactivating a Form

**To edit a form:**
1. Go to **Web Forms** list.
2. Click **Edit** on the form row.
3. Update fields — click **Save Changes**.

**To deactivate a form (stop accepting submissions):**
1. Open the Edit screen.
2. Uncheck **Form is active**.
3. Save.

Once inactive:
- The public URL (`/f/{slug}`) returns **404 Not Found**
- No new leads will be created through that form
- Existing leads from the form are unaffected

**To delete a form:**
- Only Institution Admins and Admissions Managers can delete forms.
- Deletion is a **soft delete** — the form is hidden but the data is preserved.
- All leads captured by the form remain intact in the CRM.

---

## 10. How Leads Flow In

When a student submits a public form:

```
Student fills form at /f/{slug}
         ↓
Alpine.js captures UTM params from URL
         ↓
Form is submitted via fetch() (XHR)
         ↓
CRM validates: mobile, name, email, consent
         ↓
Lead created in the database with:
  • source = form's configured source (e.g. "event")
  • source_utm_params = UTM captured from URL
  • consent_given = true
  • consent_ip = student's IP address
  • consent_form_version = form's version (e.g. "v1.0")
         ↓
Async: duplicate detection runs in background
Async: lead score calculated in background
         ↓
Counsellors see the lead in CRM → Leads
```

The lead is visible immediately in **CRM → Leads** with source badge.

---

## 11. DPDP Consent & Privacy

All public forms comply with the **Digital Personal Data Protection Act 2023 (DPDP)**.

**What is collected and stored with every submission:**

| Data | Storage |
|------|---------|
| `consent_given = true` | Always required — form submission fails without it |
| `consent_ip` | Student's IP address at time of submission |
| `consent_form_version` | Version string from the form configuration |
| `consent_timestamp` | Timestamp of submission |

**What this means for staff:**
- You must **never edit** the consent version string in a way that misrepresents what the student agreed to. Increment the version (e.g. `v1.0` → `v1.1`) when the consent text changes.
- Leads captured via web forms may be subject to a **Right to Erasure** request. The CRM handles this via the `AnonymisePIIJob` — no manual data deletion is needed.

---

## 12. Troubleshooting

| Problem | Likely Cause | Solution |
|---------|-------------|----------|
| Form returns 404 | Form is inactive | Go to Web Forms → Edit → re-activate the form |
| Form doesn't appear in `/crm/forms` | You don't have `crm.forms.view` permission | Ask your Institution Admin to assign the correct role |
| QR code shows "QR Preview" placeholder | `endroid/qr-code` package not installed | Run `composer require endroid/qr-code` on the server |
| UTM params not saved on lead | The URL didn't contain UTM params when the form was opened | Check the source URL includes `?utm_source=...` |
| Embed shows blank content | The iFrame URL is blocked by the site's CSP | Whitelist the CRM domain in your website's `Content-Security-Policy: frame-src` header |
| Submission says "This enquiry form is no longer available" | Slug not found or form deleted | Verify the slug in the URL matches an active form |
| Duplicate lead created | Mobile/email matches existing lead | Normal — the CRM flags it asynchronously. Counsellor will see the duplicate badge on the lead detail page |

---

*For technical queries, contact the MEETCS support team or refer to the A2A CRM Technical Documentation.*
