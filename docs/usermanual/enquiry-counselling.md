# Enquiry & Counselling Pipeline — User Manual

**Product:** A2A Educational CRM (Admissions-2-Alumni)  
**Company:** MEETCS Pvt. Ltd.  
**Module:** Group E — Enquiry & Counselling Pipeline  
**BRD Reference:** CRM-EC-001 to CRM-EC-019  
**Last Updated:** April 2026

---

## 1. Overview

The Enquiry & Counselling Pipeline module transforms raw lead records into qualified, counselled applicants ready for enrolment. It provides:

- **Activity Timeline** — complete chronological log of all lead interactions
- **Counsellor Assignment** — automatic (round-robin / load-balanced) and manual reassignment
- **Session Scheduling** — book, manage and record counselling appointments
- **Public Booking** — leads can self-book appointments via a public URL
- **Appointment Reminders** — automatic 24h and 1h reminder notifications
- **Escalation Alerts** — unactioned leads are automatically flagged past a configurable threshold

---

## 2. Who Can Use This Module

| Role | Activity Timeline | Assign Counsellor | Book Sessions | Config Assignment | Workload View |
|------|:-:|:-:|:-:|:-:|:-:|
| Institution Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admissions Manager | ✅ | ✅ | ✅ | ✅ | ✅ |
| Counsellor | ✅ (own leads) | ❌ | ✅ | ❌ | ✅ |
| Telecaller | ✅ | ❌ | ❌ | ❌ | ❌ |

> **Permission note:** Assignment requires the `crm.leads.assign` permission. Settings pages require `crm.settings.manage`.

---

## 3. Activity Timeline

### What is the Activity Timeline?

Every interaction — note, status change, call, email, document upload, system event — is recorded on the lead's **Activity Timeline** tab. The timeline is chronologically ordered, most-recent first.

### Adding a Note

1. Open any lead record: **CRM → Leads → [Lead Name]**
2. Click the **Timeline** tab.
3. Scroll to the **Add Note** form at the top.
4. Type your note (max 2,000 characters).
5. Click **Add Note** — the entry appears immediately without page reload.

> **DPDP Notice:** Never include the student's mobile number, email address, Aadhaar, or other PII directly in notes. Use the structured fields on the lead record instead.

### Activity Types

| Icon | Type | When Logged |
|------|------|-------------|
| 📝 | Note | Manual staff note |
| 🔄 | Status Change | Lead status transitions |
| 👤 | Assignment | Lead assigned/reassigned to counsellor |
| 📞 | Call Logged | Call outcome recorded via Telecalling module |
| 📧 | Email Sent | Outbound email |
| 💬 | WhatsApp Sent | WhatsApp message |
| 💼 | System | Automated system events |

---

## 4. Counsellor Assignment

### Automatic Assignment (BRD: CRM-EC-006)

When a new lead is created, it can be automatically assigned to a counsellor based on the institution's configured mode:

| Mode | Description |
|------|-------------|
| **Round Robin** | Leads distributed in rotation; each counsellor receives one lead before the cycle restarts |
| **Load Balanced** | Lead assigned to the counsellor with the fewest active leads |
| **Manual** | No automatic assignment; manager manually assigns every lead |

### Configuring Assignment Mode

1. Go to **CRM → Settings → Assignment Configuration** (`/crm/settings/assignment-config`)
2. Select the **Assignment Mode**.
3. Set the **Max Leads Per Counsellor** (the cap at which auto-assignment stops).
4. Configure the **Escalation Threshold** (hours after which unactioned leads are flagged).
5. Click **Save Configuration**.

### Manual Reassignment (BRD: CRM-EC-007)

1. Open the lead record.
2. In the **Assign Counsellor** card on the left sidebar, click **Reassign Counsellor**.
3. Enter the **Counsellor User ID** in the input field.
4. Click **Confirm Assignment**.

The page refreshes automatically on success.

### Counsellor Workload Dashboard (BRD: CRM-EC-008)

Navigate to **CRM → Counsellors → Workload** (`/crm/counsellors/workload`) to see a real-time bar chart of active lead counts per counsellor, colour-coded:

- 🟢 **Green** — below 50% capacity
- 🟡 **Amber** — 50–79% capacity
- 🔴 **Red** — 80%+ capacity (approaching cap)

---

## 5. Counselling Sessions (BRD: CRM-EC-015)

### Booking a Session (Internal Staff)

1. Open a lead record.
2. Click the **Sessions** tab.
3. In the **Book Session** form:
   - Select the **Counsellor** (by User ID).
   - Choose a **Date** — the **Time** dropdown auto-populates with available slots.
   - Select **Session Type**: Initial Counselling, Follow-up, Group, or Walk-in.
   - Select **Mode**: Online, In-Person, or Phone.
   - Optionally add **Pre-session Notes**.
4. Click **Book Session**.

The session appears in the list immediately and a confirmation notification is sent.

### Recording Session Outcomes

From the Sessions list on the lead record, when a session is complete:

1. On the session row, the **Cancel** button allows immediate cancellation.
2. For outcome recording (completed/no-show), use the PUT API endpoint or the session update form (available via the session management screens).

### Session Status Flow

```
SCHEDULED → CONFIRMED → COMPLETED
                 ↓
           CANCELLED / NO_SHOW / RESCHEDULED
```

---

## 6. Public Booking Page (BRD: CRM-EC-016)

Leads can self-schedule a counselling session using a **public booking URL**:

```
https://[your-domain]/book/{lead-uuid}
```

> The booking page is only accessible if the lead's **consent_given** flag is `true`. Never share booking URLs without the student's prior consent.

### How It Works

1. The lead opens the booking URL in their browser (no login required).
2. They select:
   - A **Counsellor** from the dropdown.
   - A **Date** and **Time** from available slots.
   - **Mode** (online/phone/in-person).
3. They click **Confirm Booking**.
4. A confirmation page is displayed and the session is created in the CRM.

---

## 7. Appointment Reminders (BRD: CRM-EC-017)

Reminders are sent automatically:

| Trigger | Recipient | Channel |
|---------|-----------|---------|
| 24 hours before session | Lead (via email) | Email + In-app notification |
| 1 hour before session | Lead (via email) | Email + In-app notification |

Reminder flags (`reminder_24h_sent`, `reminder_1h_sent`) are tracked on the session record so reminders are sent exactly once. The scheduler runs every 30 minutes.

---

## 8. Escalation Alerts (BRD: CRM-EC-009)

When a lead remains uncontacted beyond the configured **Escalation Hours** threshold, the assigned counsellor (or the designated escalation user) receives:

- An **email notification** with the lead UUID and current status.
- An **in-app notification** visible in the notification bell.

**No PII** (name, mobile, email) is included in any notification body — only the anonymised lead UUID.

---

## 9. Lead Status Transitions (BRD: CRM-EC-011 to EC-014)

| Status | Triggered By |
|--------|-------------|
| `new` | Lead creation |
| `contacted` | First call/email logged |
| `counselling_scheduled` | Session booked |
| `counselling_done` | Session completed |
| `application_started` | Application form opened |
| `lost` | Manually marked lost (requires a reason) |

When marking a lead as **Lost**, you must select a **Lost Reason**:

- Not Interested
- Joined Competitor
- Financial Constraint
- Personal Reason
- No Response
- Programme Not Suited
- Deferred to Next Cycle

---

## 10. Troubleshooting

| Issue | Solution |
|-------|----------|
| "No available slots" shown for a date | The counsellor has no active availability configured for that day. Ask the admin to add availability slots. |
| Public booking page shows 404 | The lead UUID is invalid or the lead's consent flag is false. Verify consent before sharing the link. |
| Auto-assignment not working | Check that the assignment mode is not set to Manual, and that at least one counsellor has an active account under the cap. |
| Reminder not received | Check the `reminder_24h_sent` / `reminder_1h_sent` flags on the session record (may have already been sent). Verify the lead has a valid email on file. |

---

*For technical documentation see [Phase1_Sprint_Master_Plan.md](../Phase1_Sprint_Master_Plan.md) — Group E section.*
