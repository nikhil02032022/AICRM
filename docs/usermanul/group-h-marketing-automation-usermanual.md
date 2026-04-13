# A2A CRM - Group H User Manual
## Marketing Automation and Attribution (Sprint 2)
**Version:** 1.0
**Date:** April 2026
**Module:** Group H (LC-005, LC-006, LC-013, LC-016, LC-017, MA-001 to MA-010)

---

## 1. Overview
Group H focuses on marketing-led lead generation and attribution.

### Current implementation status (code-verified)
| Requirement | Status | Notes |
|---|---|---|
| LC-005 Landing Page Builder | Implemented | CRM CRUD, public `/lp/{slug}`, form embed integration, API + public tests |
| LC-016 Multi-touch attribution | Partial | UTM capture/forwarding in landing-page flow only; no attribution ledger/reporting |
| LC-006 Website chatbot widget | Implemented (initial slice) | Public chatbot + lead capture + CRM lead creation + API ingestion |
| LC-013 Walk-in kiosk interface | Not Implemented | Walk-in source exists, but no kiosk UI/controller flow |
| LC-017 Cost-per-lead tracking | Not Implemented | Planned only |
| MA-001 to MA-010 Automation engine | Implemented | MA-001/002/003/004/005/006/007/008/009/010 implemented |

### Currently available in this build
- LC-005 Landing Page Builder (initial slice)
  - Create and manage campaign landing pages in CRM.
  - Publish public landing pages at `/lp/{slug}`.
  - Attach existing CRM Web Forms to capture enquiries.
  - Pass UTM attribution parameters to embedded form URLs.
- LC-006 Website Chatbot (initial slice)
   - Generate an institution-specific chatbot embed URL from CRM.
   - Embed chatbot on website using iframe.
   - Capture one query + lead details + consent in public widget flow.
   - Auto-create CRM lead with source `live_chat`.
   - View captured chatbot enquiries in CRM with processing status.
- LC-016 Attribution support (partial)
   - Capture landing-page UTM fields and append them to the embedded form URL.
   - Full multi-touch attribution model/reporting is not available yet.
- Marketing Automation (partial)
   - MA-001 workflow builder CRUD is available.
   - MA-002 trigger evaluation is available for event, date/time, and inactivity trigger families.
   - MA-003 action runtime is available for supported action types.
   - MA-004 A/B testing is available for automated email subject/content variants.
   - MA-005 drip delay scheduling is available per workflow step.
   - MA-006 auto-exits nurture sequences when lead status progresses to Contacted or higher.
   - MA-007 re-engagement workflows are available for cold and inactive lead scenarios.
   - MA-008 programme-specific nurture journeys are available through `programme_ids` / `programme_codes` trigger configuration.
   - MA-009 event-based journeys are available using `event_based` trigger config (`event_type`, `event_at`, `window_minutes`, `reminder_offsets_days`).
   - MA-010 automation performance reporting is available through `/api/v1/crm/automation/workflows-performance` with workflow-level KPIs.

### Planned (not fully available yet)
- LC-013 Walk-in kiosk UI
- LC-016 Multi-touch attribution model
- LC-017 Cost-per-lead tracking
- None in MA scope (MA-001 to MA-010 complete)

---

## 2. Who Can Use It
Landing Page management is permission-based.

| Role Permission | Access |
|---|---|
| `crm.campaigns.manage` | Can create, edit, publish, and delete landing pages |
| `crm.chat-widget.manage` | Can access LC-006 website chatbot setup and captured enquiries |
| Without permission | Cannot access Group H landing page management screens |

---

## 3. Navigation
1. Login to CRM.
2. Open left sidebar.
3. Go to **Landing Pages**.
4. You will land on the list screen (`CRM -> Marketing -> Landing Pages`).

---

## 4. Create a Landing Page (LC-005)
1. Click **New Landing Page**.
2. Fill **Page Identity**:
   - Internal Name
   - Public Slug (used in `/lp/{slug}`)
   - Status (`draft`, `published`, `archived`)
3. Fill **Hero Section**:
   - Headline
   - Subheadline
   - Hero Image URL (optional)
   - Theme Variant (`scholar`, `sunrise`, `forest`)
4. Fill **Value Sections** (up to 3 content cards):
   - Eyebrow
   - Title
   - Body
5. Fill **Lead Capture and Attribution**:
   - Link a Web Form
   - CTA labels
   - UTM fields: `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`
6. Fill **SEO Metadata**:
   - SEO title
   - SEO description
7. Click **Create Landing Page**.

Result:
- Draft pages are managed internally.
- Published pages become accessible publicly at `/lp/{slug}`.

---

## 5. Edit, Publish, Archive, Delete
### Edit
1. Open Landing Pages list.
2. Click **Edit** on a row.
3. Update content and click **Save Changes**.

### Publish
1. Set **Status** to `published`.
2. Save changes.
3. Use **Open Public Page** button to verify output.

### Archive
1. Set **Status** to `archived`.
2. Save changes.
3. Archived pages are not publicly visible.

### Delete
1. In list view, click **Delete**.
2. Confirm prompt.
3. Record is soft-deleted and removed from active list.

---

## 6. Public Page Behavior
- Public URL pattern: `/lp/{slug}`
- Only `published` pages are publicly visible.
- `draft` or `archived` pages return 404.
- If a Web Form is linked, the page embeds the existing form URL (`/f/{slug}/embed`) with attribution query params.

Note:
- Attribution here means UTM parameter forwarding through the web-form embed URL in the current build.
- Multi-touch attribution history (first/last/linear touch ledger) is still pending implementation.

---

## 7. Attribution Handling
Group H currently supports UTM attribution forwarding only in landing-page-driven form embeds.

## 8. Website Chatbot (LC-006) - How to Use

LC-006 is available as an embeddable website chatbot for institution lead capture.

### A. Open chat widget management in CRM
1. Login to CRM with a role that has `crm.chat-widget.manage`.
2. Navigate to **CRM -> Marketing -> Website Chatbot**.
3. Review:
   - **Open Widget Preview** link
   - **Embed Snippet** iframe code
   - **Lead ingestion endpoint** URL
   - Captured enquiry list

### B. Embed the widget on your website
1. Copy the iframe snippet from the CRM page.
2. Paste it into the target page of your institution website.
3. Keep the institution-specific widget URL unchanged.

Public route format:
- `GET /chat/widget/{institution_uuid}`

Submission route format:
- `POST /chat/widget/{institution_uuid}/submit`

### C. Capture an enquiry from the widget
1. Visitor opens the embedded widget.
2. Visitor fills lead details:
   - First name
   - Last name
   - Mobile
   - Email (optional)
3. Visitor enters one query message (course/campus/intake related).
4. Visitor accepts consent checkbox.
5. Visitor clicks **Submit Enquiry**.

System behavior:
- Chat submission is validated (mobile format, consent required, transcript schema).
- A chatbot enquiry row is stored in `chat_leads`.
- A CRM lead is auto-created with source `live_chat`.
- UTM params (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`) are stored when present.

### D. Verify submissions in CRM
1. Return to **CRM -> Marketing -> Website Chatbot**.
2. Open **Captured Chatbot Leads** table.
3. Verify:
   - Session ID
   - Linked Lead
   - Conversation thread via **View Chat Transcript**
   - Source URL
   - Captured timestamp
   - Processing status (`Queued` or `Processed`)

### D1. Discuss using captured chat context
1. In **Captured Chatbot Leads**, click **View Chat Transcript** to open the captured query in a modal.
2. Review student and assistant messages.
3. Click **Review And Contact Lead** to continue follow-up from the linked lead profile.
4. Use lead profile communication actions (call, email, WhatsApp/SMS where configured) for outreach.

### E. API usage (external integrations)
For mobile/ERP/approved integrations (not CRM web UI), use:
- `POST /api/v1/crm/chat-widget/leads`
- `GET /api/v1/crm/chat-widget/leads`
- `GET /api/v1/crm/chat-widget/leads/{chat_lead}`

Auth and contract:
- Sanctum token auth required.
- Typical required fields for create: `session_id`, `first_name`, `last_name`, `mobile`, `consent_given`, `consent_form_version`.

### Supported params
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_term`
- `utm_content`

This allows existing form lead-capture logic to retain source attribution without duplicating form processing.

Not yet available in current build:
- Multi-touch attribution ledger per lead
- First-touch vs last-touch vs linear attribution logic
- Attribution reports/dashboard

---

## 9. DPDP and Compliance Notes
- Consent capture remains in the linked Web Form flow.
- Do not log personal identifiers in manual notes or debugging logs.
- Use approved institution forms and consent versions only.
- Publish only reviewed pages linked to consent-enabled forms.

---

## 10. Troubleshooting
### Landing page not opening publicly
- Check status is `published`.
- Verify slug is correct.
- Confirm page was not deleted/archived.

### Form is not visible on public page
- Ensure **Linked Web Form** is selected.
- Confirm linked form is active and accessible.

### UTM values not appearing downstream
- Verify UTM fields are set on landing page.
- Confirm linked form embed is used (not a custom external form).
- Confirm landing page has a linked active web form and is tested via the public `/lp/{slug}` URL.

### Chat widget not opening
- Confirm institution is active.
- Verify widget URL uses the institution UUID from CRM embed snippet.

### Chat submission fails
- Confirm consent checkbox is selected.
- Verify mobile matches the required 10-digit format.
- Check required fields: `session_id`, `first_name`, `last_name`, `mobile`, `consent_given`, `consent_form_version`.

### Kiosk/automation options not visible
- Kiosk and automation capabilities are still pending in this build.

### Access denied in CRM
- For landing pages, ask admin to grant `crm.campaigns.manage`.
- For chat widget, ask admin to grant `crm.chat-widget.manage`.

---

## 11. Quick Operational Checklist
Before campaign launch:
1. Landing page status set to `published`.
2. Correct Web Form linked.
3. UTM values configured.
4. Public URL tested.
5. Consent text verified in linked form.
6. Chat widget iframe embedded and preview-tested.
7. Chat consent capture verified on submission.

---

## 12. Release Notes (Group H - Current Slice)
Delivered now:
- Landing page CRUD (CRM)
- Public landing page rendering
- Form embed integration with attribution forwarding
- Live chat widget embed and public submission flow
- Website chatbot embed and public submission flow
- Auto-created CRM leads from chat sessions (`source=live_chat`)
- Refreshed LC-006 CRM and public chat widget UI for improved readability and operator flow
- CRM admin view updated to table format with Lead Name, Status, and action buttons (Agent Reply modal + Review and Contact)
- Fixed LC-006 admin reply modal viewport overflow to prevent unnecessary internal vertical scrollbar
- Stabilized LC-006 admin reply modal default hidden state to avoid layout/scroll artifacts before Alpine state applies
- Fully redesigned LC-006 CRM Website Chatbot operations page with modern visual hierarchy, search/filter UX, and single shared Agent Reply modal
- Baseline API and public behavior tests
- Partial attribution support via UTM capture/forwarding only

Pending for full Group H scope:
- Real-time two-way live agent chat
- Kiosk mode
- Multi-touch attribution ledger
- Cost-per-lead dashboard
- Visual workflow automation engine
