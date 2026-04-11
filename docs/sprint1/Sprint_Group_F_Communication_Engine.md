# Sprint Plan — Group F: Communication Engine
**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Date:** April 2026  
**Scope:** CRM-LC-007 · CRM-LC-010 · CRM-CC-001 to CRM-CC-023  
**Status:** 🔴 Not Started  
**Depends on:** Group A ✅ · Group B ✅ · Group C ✅ · Group D ✅ · Group E ✅

---

## 1. BRD Requirements in Scope

| Req ID | Requirement | Priority | Channel |
|--------|-------------|----------|---------|
| **CRM-LC-007** | WhatsApp Click-to-Chat links shall auto-create lead records when a prospective student initiates a conversation | Must Have | WhatsApp |
| **CRM-LC-010** | Inbound calls to a virtual number shall automatically create lead records with caller details and call recording | Must Have | IVR/Voice |
| **CRM-CC-001** | Drag-and-drop email template builder with merge tags for personalisation | Must Have | Email |
| **CRM-CC-002** | Emails sendable individually (lead record) or in bulk (campaign/segment) | Must Have | Email |
| **CRM-CC-003** | Email delivery, open, click, bounce and unsubscribe events tracked and logged to lead | Must Have | Email |
| **CRM-CC-004** | Custom sender domain with SPF/DKIM/DMARC support | Must Have | Email |
| **CRM-CC-005** | Unsubscribe management DPDP Act compliant (opt-out respected and logged) | Must Have | Email |
| **CRM-CC-006** | SMS sendable to individual leads or in bulk with personalisation | Must Have | SMS |
| **CRM-CC-007** | Integration with MSG91, Textlocal, Kaleyra via configurable API | Must Have | SMS |
| **CRM-CC-008** | DLT template registration workflow built into SMS module | Must Have | SMS |
| **CRM-CC-009** | SMS delivery status tracked and logged | Must Have | SMS |
| **CRM-CC-010** | WhatsApp Business API via approved BSP | Must Have | WhatsApp |
| **CRM-CC-011** | Template-based WhatsApp messages for transactional triggers | Must Have | WhatsApp |
| **CRM-CC-012** | Counsellors send/receive WhatsApp messages from shared inbox | Must Have | WhatsApp |
| **CRM-CC-013** | WhatsApp chatbot for FAQs, programme info, appointment booking | Should Have | WhatsApp |
| **CRM-CC-014** | WhatsApp message delivery, read and reply events tracked | Must Have | WhatsApp |
| **CRM-CC-015** | Bulk WhatsApp broadcasts to segmented lead lists | Must Have | WhatsApp |
| **CRM-CC-016** | Cloud telephony (Exotel, Ozonetel, Knowlarity) for outbound calling | Must Have | Voice |
| **CRM-CC-017** | Click-to-call from lead record | Must Have | Voice |
| **CRM-CC-018** | Calls logged automatically with duration, disposition and recording (consented) | Must Have | Voice |
| **CRM-CC-019** | IVR for inbound enquiries — configurable with lead capture | Should Have | Voice |
| **CRM-CC-020** | Missed call campaigns (missed-call-to-lead-creation) | Should Have | Voice |
| **CRM-CC-021** | Unified communication inbox consolidating email, WhatsApp, chat per counsellor | Must Have | Inbox |
| **CRM-CC-022** | All communications appear in lead's activity timeline chronologically | Must Have | Inbox |
| **CRM-CC-023** | Counsellors notified (in-app, email, mobile push) on new inbound message | Must Have | Inbox |

**DPDP Compliance Requirements active throughout Group F:**
- **CRM-CR-001** — Consent must be captured at lead creation; WhatsApp/IVR leads must set `consent_given = true` only after first opt-in or call
- **CRM-CR-004** — Call recording requires `call_consent_given = true` before recording starts
- **CRM-CR-005** — Unsubscribe/DNC must take effect within 24 hours
- **CRM-CR-006** — No PII in logs

---

## 2. Architecture Design

### 2.1 Module Decomposition — 5 Sub-Groups

Group F is delivered as 5 cohesive sub-groups, each deployable independently:

| Sub-Group | Theme | BRD Req IDs |
|-----------|-------|-------------|
| **F1** | Email Communication Engine | CC-001 to CC-005 |
| **F2** | SMS Communication | CC-006 to CC-009 |
| **F3** | WhatsApp BSP + LC-007 | CC-010 to CC-015, LC-007 |
| **F4** | Voice, IVR, Click-to-Call + LC-010 | CC-016 to CC-020, LC-010 |
| **F5** | Unified Inbox + Notifications | CC-021 to CC-023 |

---

### 2.2 New Database Entities (10 Migrations)

#### F1 — Communication Templates (shared across all channels)
```
communication_templates (table)
├── id, uuid
├── institution_id, campus_id
├── name (internal label)
├── channel (CommunicationChannel enum: EMAIL, SMS, WHATSAPP)
├── type (TemplateType enum: TRANSACTIONAL, MARKETING, OTP, NOTIFICATION)
├── subject (nullable — email only)
├── body_html (text — email HTML, nullable for SMS/WA)
├── body_text (text — plain text / SMS / WhatsApp body)
├── merge_tags (json — list of available {{tag}} tokens)
├── is_active (bool, default true)
├── created_by (FK users.id)
├── timestamps, softDeletes
└── indexes: institution_id, channel, type, is_active
```

#### F1 — Email Campaigns
```
email_campaigns (table)
├── id, uuid
├── institution_id, campus_id
├── name, subject
├── template_id (FK communication_templates)
├── from_name, from_email (validated against sender domains)
├── status (CampaignStatus enum: DRAFT, SCHEDULED, SENDING, SENT, PAUSED, CANCELLED)
├── scheduled_at (nullable datetime)
├── recipient_filter (json — lead segment criteria)
├── total_recipients, sent_count, delivered_count, opened_count, clicked_count, bounced_count, unsubscribed_count
├── created_by (FK users.id)
├── sent_at (nullable)
├── timestamps, softDeletes
└── indexes: institution_id, status, scheduled_at
```

#### F2 — DLT Templates
```
dlt_templates (table)
├── id, uuid
├── institution_id
├── sender_id (6-char DLT registered sender)
├── template_name (internal label)
├── dlt_template_id (TRAI/DLT issued ID, nullable until approved)
├── template_body (text — approved DLT message body with variables)
├── message_type (DltMessageType enum: TRANSACTIONAL, PROMOTIONAL, OTP, SERVICE)
├── gateway (SmsGateway enum: MSG91, TEXTLOCAL, KALEYRA)
├── status (DltTemplateStatus enum: DRAFT, PENDING_APPROVAL, APPROVED, REJECTED)
├── approval_notes (nullable)
├── submitted_at, approved_at (nullable)
├── timestamps, softDeletes
└── indexes: institution_id, gateway, status
```

#### F2 — SMS Campaigns
```
sms_campaigns (table)
├── id, uuid
├── institution_id, campus_id
├── name
├── dlt_template_id (FK dlt_templates)
├── gateway (SmsGateway enum)
├── status (CampaignStatus enum)
├── recipient_filter (json)
├── total_recipients, sent_count, delivered_count, failed_count
├── scheduled_at, sent_at (nullable)
├── created_by
├── timestamps, softDeletes
└── indexes: institution_id, status
```

#### F3 — WhatsApp Conversations (shared inbox)
```
whatsapp_conversations (table)
├── id, uuid
├── institution_id, campus_id
├── lead_id (FK leads — nullable if not yet matched)
├── bsp_conversation_id (BSP-assigned conversation ID — string)
├── wa_phone_number (encrypted — contact's WhatsApp number)
├── wa_display_name (encrypted — contact's WA profile name)
├── assigned_user_id (FK users — assigned counsellor)
├── status (ConversationStatus enum: OPEN, PENDING, RESOLVED, EXPIRED)
├── last_message_at
├── is_bot_active (bool — whether chatbot is handling this conversation)
├── timestamps, softDeletes
└── indexes: institution_id, lead_id, status, bsp_conversation_id
```

```
whatsapp_messages (table)
├── id, uuid
├── conversation_id (FK whatsapp_conversations)
├── institution_id
├── bsp_message_id (BSP-assigned message ID — for dedup and tracking)
├── direction (MessageDirection enum: INBOUND, OUTBOUND)
├── message_type (WaMessageType enum: TEXT, IMAGE, DOCUMENT, AUDIO, TEMPLATE, INTERACTIVE)
├── body (text — encrypted for PII content)
├── template_name (nullable — if sent via template)
├── media_url (nullable — S3 URL for media messages)
├── status (MessageStatus enum: PENDING, SENT, DELIVERED, READ, FAILED)
├── delivered_at, read_at (nullable)
├── sent_by (FK users — null for inbound / bot)
├── timestamps
└── indexes: conversation_id, bsp_message_id, direction, status
```

#### F4 — Call Logs
```
call_logs (table)
├── id, uuid
├── institution_id, campus_id
├── lead_id (FK leads — nullable if IVR lead not yet created)
├── telephony_provider (TelephonyProvider enum: EXOTEL, OZONETEL, KNOWLARITY)
├── provider_call_id (provider-assigned call SID — for dedup)
├── direction (CallDirection enum: INBOUND, OUTBOUND)
├── from_number (encrypted — caller/dialler number)
├── to_number (encrypted)
├── duration_seconds (int, default 0)
├── disposition (CallDisposition enum: INTERESTED, NOT_INTERESTED, CALL_BACK, WRONG_NUMBER, NOT_REACHABLE, NUMBER_INVALID, VOICEMAIL, BUSY)
├── disposition_notes (text, nullable)
├── call_consent_given (bool — DPDP: CC-018)
├── recording_url (string, nullable — S3 URL, only if consent given)
├── status (CallStatus enum: INITIATED, RINGING, IN_PROGRESS, COMPLETED, FAILED, NO_ANSWER)
├── initiated_by (FK users — null for inbound IVR)
├── called_at, answered_at, ended_at (nullable)
├── timestamps, softDeletes
└── indexes: institution_id, lead_id, direction, status, provider_call_id
```

#### F4 — IVR Configs
```
ivr_configs (table)
├── id, uuid
├── institution_id, campus_id
├── provider (TelephonyProvider enum)
├── virtual_number (encrypted — the DID/virtual number)
├── welcome_message (text — IVR welcome TTS or audio URL)
├── collect_name (bool, default true)
├── collect_programme (bool, default true)
├── fallback_counsellor_id (FK users — who gets notified on missed/IVR lead)
├── is_active (bool)
├── credentials_id (FK integration_credentials)
├── timestamps, softDeletes
└── indexes: institution_id, is_active
```

#### F1/F2/F3/F4 — Unified Communication Log
```
communication_logs (table)
├── id, uuid
├── institution_id
├── lead_id (FK leads)
├── loggable_type, loggable_id (polymorphic — EmailCampaign, SmsCampaign, or NULL for 1:1)
├── channel (CommunicationChannel enum)
├── direction (MessageDirection enum: INBOUND, OUTBOUND)
├── template_id (FK communication_templates, nullable)
├── subject (nullable — email subject)
├── body_preview (string 500 — truncated preview, no PII)
├── status (MessageStatus enum)
├── external_id (provider message ID for dedup)
├── opened_at, clicked_at, delivered_at, bounced_at (nullable)
├── timestamps
└── indexes: institution_id, lead_id, channel, status, external_id
```

#### F5 — Sender Domain Configs (CC-004)
```
sender_domains (table)
├── id, uuid
├── institution_id
├── domain (e.g. "admissions.gim.ac.in")
├── default_from_name
├── default_from_email
├── spf_verified (bool)
├── dkim_verified (bool)
├── dmarc_verified (bool)
├── provider (EmailProvider enum: MAILGUN, POSTMARK, SES, SENDGRID)
├── credentials_id (FK integration_credentials)
├── is_default (bool)
├── verified_at (nullable)
├── timestamps, softDeletes
└── indexes: institution_id, domain, is_default
```

---

### 2.3 New Enums (7)

| Enum | Cases |
|------|-------|
| `CommunicationChannel` | EMAIL, SMS, WHATSAPP, VOICE, PUSH |
| `MessageStatus` | PENDING, SENT, DELIVERED, READ, FAILED, BOUNCED, UNSUBSCRIBED |
| `MessageDirection` | INBOUND, OUTBOUND |
| `CallDirection` | INBOUND, OUTBOUND |
| `CallDisposition` | INTERESTED, NOT_INTERESTED, CALL_BACK, WRONG_NUMBER, NOT_REACHABLE, NUMBER_INVALID, VOICEMAIL, BUSY |
| `TelephonyProvider` | EXOTEL, OZONETEL, KNOWLARITY |
| `SmsGateway` | MSG91, TEXTLOCAL, KALEYRA |
| `DltMessageType` | TRANSACTIONAL, PROMOTIONAL, OTP, SERVICE |
| `DltTemplateStatus` | DRAFT, PENDING_APPROVAL, APPROVED, REJECTED |
| `ConversationStatus` | OPEN, PENDING, RESOLVED, EXPIRED |
| `WaMessageType` | TEXT, IMAGE, DOCUMENT, AUDIO, TEMPLATE, INTERACTIVE |
| `EmailProvider` | MAILGUN, POSTMARK, SES, SENDGRID |
| `CampaignStatus` | DRAFT, SCHEDULED, SENDING, SENT, PAUSED, CANCELLED |
| `TemplateType` | TRANSACTIONAL, MARKETING, OTP, NOTIFICATION |

---

### 2.4 Service Layer Architecture

```
app/Services/CRM/Communication/
├── EmailService.php             — send individual, bulk, verify domain
├── SmsService.php               — send SMS via configurable gateway
├── WhatsAppService.php          — BSP integration, send template/session messages
├── VoiceService.php             — click-to-call, log call, fetch recording
├── IvrService.php               — IVR config management, webhook processing
├── CampaignService.php          — create/schedule/track email + SMS campaigns
├── UnifiedInboxService.php      — aggregate inbound messages per counsellor
├── TemplateService.php          — CRUD templates with merge tag rendering
└── DltTemplateService.php       — DLT registration workflow for SMS
```

### 2.5 Job Queue Topology

All async operations on dedicated queues:

| Job | Queue | Trigger |
|-----|-------|---------|
| `SendBulkEmailJob` | `crm-comms-email` | Campaign dispatch |
| `ProcessEmailWebhookJob` | `crm-comms-email` | Mailgun/Postmark webhook |
| `SendBulkSmsJob` | `crm-comms-sms` | SMS campaign dispatch |
| `ProcessSmsDeliveryJob` | `crm-comms-sms` | Gateway delivery receipt |
| `SendBulkWhatsAppJob` | `crm-comms-whatsapp` | WA broadcast dispatch |
| `ProcessInboundWhatsAppJob` | `crm-comms-whatsapp` | BSP inbound webhook → lead creation (LC-007) |
| `ProcessOutboundCallJob` | `crm-comms-voice` | Click-to-call initiation |
| `ProcessTelephonyWebhookJob` | `crm-comms-voice` | Provider call-event webhook |
| `ProcessIvrLeadCreationJob` | `crm-comms-voice` | IVR lead auto-create (LC-010) |
| `NotifyInboundMessageJob` | `crm-notifications` | New inbound message → counsellor notify (CC-023) |
| `EnforceUnsubscribeJob` | `crm-comms-email` | Unsubscribe within 24h (DPDP) |

---

### 2.6 Webhook Routes (API Rails — no session auth)

| Route | Auth | Purpose |
|-------|------|---------|
| `POST /api/v1/crm/webhooks/email/{provider}` | HMAC signature | Mailgun/Postmark event webhook |
| `POST /api/v1/crm/webhooks/sms/{gateway}` | HMAC signature | MSG91/Textlocal delivery receipt |
| `POST /api/v1/crm/webhooks/whatsapp` | Meta signature (`X-Hub-Signature-256`) | BSP inbound message + delivery status |
| `POST /api/v1/crm/webhooks/telephony/{provider}` | IP allowlist + secret | Exotel/Ozonetel call-event |
| `POST /api/v1/crm/webhooks/ivr/{provider}` | IP allowlist + secret | IVR response / missed call |

All webhooks:  
1. Verify signature with `hash_equals()` — reject 403 on mismatch  
2. Return `200 OK` **immediately** (< 300ms)  
3. Dispatch a Job to process asynchronously  
4. Never process inline in webhook controller

---

### 2.7 Web Routes (CRM App — session auth)

| Route Group | Prefix | Purpose |
|-------------|--------|---------|
| Templates | `/crm/communication/templates` | CRUD for email/SMS/WA templates |
| Email Campaigns | `/crm/communication/email` | Create, schedule, send campaigns |
| SMS Campaigns | `/crm/communication/sms` | Create, schedule, send SMS campaigns |
| DLT Templates | `/crm/communication/sms/dlt` | DLT template registration workflow |
| WhatsApp Inbox | `/crm/communication/whatsapp` | Shared inbox + conversation view |
| Voice / Call Log | `/crm/communication/voice` | Click-to-call, call history |
| IVR Config | `/crm/settings/ivr` | IVR flow builder |
| Sender Domains | `/crm/settings/sender-domains` | Domain verification |
| Unified Inbox | `/crm/inbox` | Consolidated inbound inbox |

---

## 3. Implementation Tasks — Sub-Group F1 (Email)

### Task F1-01 — Migrations (3 files)
- `2026_04_16_000001_create_communication_templates_table.php`
- `2026_04_16_000002_create_email_campaigns_table.php`
- `2026_04_16_000003_create_communication_logs_table.php`
- `2026_04_16_000004_create_sender_domains_table.php`

Add `email_unsubscribed_at`, `email_bounce_count` to `leads` table via `2026_04_16_000005_add_email_unsubscribe_to_leads_table.php`

### Task F1-02 — Enums
- `app/Enums/CRM/CommunicationChannel.php`
- `app/Enums/CRM/MessageStatus.php`
- `app/Enums/CRM/MessageDirection.php`
- `app/Enums/CRM/CampaignStatus.php`
- `app/Enums/CRM/TemplateType.php`
- `app/Enums/CRM/EmailProvider.php`

### Task F1-03 — Models
- `app/Models/CRM/CommunicationTemplate.php` — InstitutionScope, HasUuids, SoftDeletes
- `app/Models/CRM/EmailCampaign.php` — InstitutionScope, HasUuids, SoftDeletes
- `app/Models/CRM/CommunicationLog.php` — InstitutionScope, HasUuids (no soft delete — immutable log)
- `app/Models/CRM/SenderDomain.php` — InstitutionScope, HasUuids, SoftDeletes

### Task F1-04 — DTOs
- `app/DTOs/CRM/CreateCommunicationTemplateDTO.php`
- `app/DTOs/CRM/SendEmailDTO.php`
- `app/DTOs/CRM/CreateEmailCampaignDTO.php`

### Task F1-05 — Repositories
- `app/Repositories/CRM/Communication/CommunicationTemplateRepositoryInterface.php`
- `app/Repositories/CRM/Communication/EloquentCommunicationTemplateRepository.php`
- `app/Repositories/CRM/Communication/CommunicationLogRepositoryInterface.php`
- `app/Repositories/CRM/Communication/EloquentCommunicationLogRepository.php`
- `app/Repositories/CRM/Communication/EmailCampaignRepositoryInterface.php`
- `app/Repositories/CRM/Communication/EloquentEmailCampaignRepository.php`

### Task F1-06 — Services
```php
// app/Services/CRM/Communication/TemplateService.php
create(CreateCommunicationTemplateDTO, int $institutionId): CommunicationTemplate
update(CommunicationTemplate, array): CommunicationTemplate
render(CommunicationTemplate, array $mergeData): string  // replaces {{tokens}}
delete(CommunicationTemplate): void
paginate(array $filters): LengthAwarePaginator

// app/Services/CRM/Communication/EmailService.php
// BRD: CRM-CC-002 — Send individual email from lead record
sendToLead(Lead, SendEmailDTO): CommunicationLog
// BRD: CRM-CC-002 — Dispatch bulk email campaign
dispatchCampaign(EmailCampaign): void  // fan-out → SendBulkEmailJob per recipient
// BRD: CRM-CC-003 — Handle delivery event from webhook
handleDeliveryEvent(array $webhookPayload, string $provider): void
// BRD: CRM-CC-005 — DPDP unsubscribe
unsubscribeLead(Lead, string $reason): void  // dispatches EnforceUnsubscribeJob
// BRD: CRM-CC-004 — Check/update sender domain verification
verifySenderDomain(SenderDomain): SenderDomain
```

### Task F1-07 — Jobs
- `app/Jobs/CRM/Communication/SendBulkEmailJob.php` — per-recipient, idempotent (`unique_id` = `email_campaign_id:lead_id`)
- `app/Jobs/CRM/Communication/ProcessEmailWebhookJob.php` — parse event, update `communication_logs`, fire events
- `app/Jobs/CRM/Communication/EnforceUnsubscribeJob.php` — sets `email_unsubscribed_at`, logs to `audit_logs`

### Task F1-08 — Events + Listeners
Events: `EmailSentEvent` · `EmailOpenedEvent` · `EmailBouncedEvent` · `EmailUnsubscribedEvent`  
Listeners: `LogEmailSentToActivity` · `HandleEmailBounce` · `HandleLeadUnsubscribe`

### Task F1-09 — Form Requests
- `app/Http/Requests/CRM/StoreCommunicationTemplateRequest.php`
- `app/Http/Requests/CRM/SendEmailRequest.php`
- `app/Http/Requests/CRM/CreateEmailCampaignRequest.php`

### Task F1-10 — Controllers
```
app/Http/Controllers/CRM/Web/Communication/CommunicationTemplateWebController.php
app/Http/Controllers/CRM/Web/Communication/EmailCampaignWebController.php
app/Http/Controllers/CRM/Web/Communication/SenderDomainWebController.php
app/Http/Controllers/CRM/Api/Webhooks/EmailWebhookController.php  ← API only
```

### Task F1-11 — Blade Views
```
resources/views/crm/communication/templates/index.blade.php
resources/views/crm/communication/templates/create.blade.php
resources/views/crm/communication/templates/edit.blade.php
resources/views/crm/communication/email/campaigns/index.blade.php
resources/views/crm/communication/email/campaigns/create.blade.php
resources/views/crm/communication/email/campaigns/show.blade.php  (live stats)
resources/views/crm/communication/email/compose.blade.php  (1:1 from lead record)
resources/views/crm/settings/sender-domains/index.blade.php
resources/views/crm/settings/sender-domains/verify.blade.php
```

---

## 4. Implementation Tasks — Sub-Group F2 (SMS)

### Task F2-01 — Migrations
- `2026_04_17_000001_create_dlt_templates_table.php`
- `2026_04_17_000002_create_sms_campaigns_table.php`

Add `sms_unsubscribed_at`, `dnc_at` to `leads` table via `2026_04_17_000003_add_sms_unsubscribe_to_leads_table.php`

### Task F2-02 — Enums
- `app/Enums/CRM/SmsGateway.php`
- `app/Enums/CRM/DltMessageType.php`
- `app/Enums/CRM/DltTemplateStatus.php`

### Task F2-03 — Models
- `app/Models/CRM/DltTemplate.php` — InstitutionScope, HasUuids, SoftDeletes
- `app/Models/CRM/SmsCampaign.php` — InstitutionScope, HasUuids, SoftDeletes

### Task F2-04 — Services
```php
// app/Services/CRM/Communication/SmsService.php
// BRD: CRM-CC-006 — Send individual SMS
sendToLead(Lead, string $message, DltTemplate $template): CommunicationLog
// BRD: CRM-CC-006 — Dispatch bulk SMS campaign
dispatchSmsCampaign(SmsCampaign): void  // fan-out → SendBulkSmsJob
// BRD: CRM-CC-009 — Handle delivery receipt from gateway
handleDeliveryReceipt(array $payload, string $gateway): void
// BRD: CRM-CC-005 compatible — DNC/opt-out
optOutLead(Lead): void  // sets sms_unsubscribed_at + dnc_at

// app/Services/CRM/Communication/DltTemplateService.php
// BRD: CRM-CC-008 — Manage DLT template registration
create(array $data, int $institutionId): DltTemplate
submitForApproval(DltTemplate): DltTemplate
markApproved(DltTemplate, string $dltId): DltTemplate
markRejected(DltTemplate, string $notes): DltTemplate
```

### Task F2-05 — Gateway Adapter Pattern
Follows Strategy pattern — all gateways implement `SmsGatewayInterface`:
```php
interface SmsGatewayInterface {
    public function send(string $to, string $message, string $senderId): array;
    public function verifyWebhookSignature(Request $request): bool;
    public function parseDeliveryReceipt(array $payload): array;
}
```
Implementations:
- `app/Services/CRM/Communication/Gateways/Msg91Gateway.php`
- `app/Services/CRM/Communication/Gateways/TextlocalGateway.php`
- `app/Services/CRM/Communication/Gateways/KaleyraGateway.php`

### Task F2-06 — Jobs + Webhooks + Controllers
Jobs: `SendBulkSmsJob` · `ProcessSmsDeliveryJob`  
Controllers:
- `app/Http/Controllers/CRM/Web/Communication/SmsCampaignWebController.php`
- `app/Http/Controllers/CRM/Web/Communication/DltTemplateWebController.php`
- `app/Http/Controllers/CRM/Api/Webhooks/SmsGatewayWebhookController.php`  ← API only

---

## 5. Implementation Tasks — Sub-Group F3 (WhatsApp + LC-007)

### Task F3-01 — Migrations
- `2026_04_18_000001_create_whatsapp_conversations_table.php`
- `2026_04_18_000002_create_whatsapp_messages_table.php`

### Task F3-02 — Enums
- `app/Enums/CRM/ConversationStatus.php`
- `app/Enums/CRM/WaMessageType.php`
- `app/Enums/CRM/MessageDirection.php` (shared with F1)

### Task F3-03 — Models
- `app/Models/CRM/WhatsAppConversation.php` — InstitutionScope, HasUuids, SoftDeletes
- `app/Models/CRM/WhatsAppMessage.php` — no soft delete (message log is immutable)

### Task F3-04 — BSP Adapter Pattern
All BSPs implement `WhatsAppBspInterface`:
```php
interface WhatsAppBspInterface {
    public function sendTemplate(string $to, string $templateName, array $params): array;
    public function sendText(string $to, string $message, ?string $contextMessageId = null): array;
    public function verifyWebhookSignature(Request $request): bool;
    public function parseInboundMessage(array $payload): array;
    public function parseDeliveryStatus(array $payload): array;
    public function markConversationRead(string $messageId): void;
}
```
Implementations:
- `app/Services/CRM/Communication/BSP/MetaCloudBsp.php` (Meta Cloud API v17+)
- `app/Services/CRM/Communication/BSP/InteraktBsp.php`
- `app/Services/CRM/Communication/BSP/GupshupBsp.php`

### Task F3-05 — WhatsApp Service
```php
// app/Services/CRM/Communication/WhatsAppService.php

// BRD: CRM-CC-011 — Send template-based message
sendTemplate(Lead, string $templateName, array $params): CommunicationLog

// BRD: CRM-CC-012 — Send session/free-form message from shared inbox
sendMessage(WhatsAppConversation, string $message, User $sender): WhatsAppMessage

// BRD: CRM-LC-007 — Process inbound message → auto-create lead if not matched
handleInboundMessage(array $bspPayload): void  // delegates to ProcessInboundWhatsAppJob

// BRD: CRM-CC-014 — Update delivery/read status from BSP webhook
updateMessageStatus(string $bspMessageId, MessageStatus $status): void

// BRD: CRM-CC-015 — Dispatch bulk WA broadcast
dispatchBroadcast(CommunicationTemplate, array $leadIds): void
```

### Task F3-06 — LC-007: WhatsApp Click-to-Chat Lead Auto-Creation
The `ProcessInboundWhatsAppJob` implements the lead auto-creation flow:

```
Inbound WhatsApp webhook →
  1. Verify Meta signature: hash_equals(hmac-sha256, X-Hub-Signature-256)
  2. ACK 200 OK immediately
  3. Dispatch ProcessInboundWhatsAppJob
  4. Job: check if phone matches existing lead (institution-scoped)
     a. Match found → attach message to conversation, update timeline
     b. No match → auto-create lead:
          LeadSource::WHATSAPP
          consent_given = false  (DPDP: set true only after explicit opt-in message)
          status = NEW_ENQUIRY
          phone = wa_phone_number (encrypted)
          Dispatch DetectLeadDuplicatesJob
          Fire WhatsAppLeadCreatedEvent
  5. Send welcome template message (configured per institution)
```

### Task F3-07 — Livewire Components
- `app/Livewire/CRM/Communication/WhatsAppInbox.php` + view — paginated conversation list
- `app/Livewire/CRM/Communication/ConversationThread.php` + view — real-time message thread (polls every 5s)

### Task F3-08 — Controllers
```
app/Http/Controllers/CRM/Web/Communication/WhatsAppWebController.php
app/Http/Controllers/CRM/Api/Webhooks/WhatsAppWebhookController.php  ← API only
```

### Task F3-09 — Events + Jobs
Events: `WhatsAppMessageSentEvent` · `WhatsAppMessageReceivedEvent` · `WhatsAppLeadCreatedEvent`  
Jobs: `ProcessInboundWhatsAppJob` · `SendBulkWhatsAppJob`  
Listeners: `LogWhatsAppToActivityTimeline` · `NotifyAssignedCounsellorOnInbound`

---

## 6. Implementation Tasks — Sub-Group F4 (Voice + IVR + LC-010)

### Task F4-01 — Migrations
- `2026_04_19_000001_create_call_logs_table.php`
- `2026_04_19_000002_create_ivr_configs_table.php`

### Task F4-02 — Enums
- `app/Enums/CRM/TelephonyProvider.php`
- `app/Enums/CRM/CallDirection.php`
- `app/Enums/CRM/CallDisposition.php`
- `app/Enums/CRM/CallStatus.php`

### Task F4-03 — Models
- `app/Models/CRM/CallLog.php` — InstitutionScope, HasUuids, SoftDeletes
- `app/Models/CRM/IvrConfig.php` — InstitutionScope, HasUuids, SoftDeletes

### Task F4-04 — Telephony Provider Adapter Pattern
```php
interface TelephonyProviderInterface {
    public function initiateCall(string $from, string $to, array $options): array;
    public function terminateCall(string $callId): void;
    public function verifyWebhookSignature(Request $request): bool;
    public function parseCallEvent(array $payload): array;
    public function getRecordingUrl(string $callId): ?string;
}
```
Implementations:
- `app/Services/CRM/Communication/Telephony/ExotelProvider.php`
- `app/Services/CRM/Communication/Telephony/OzonetelProvider.php`
- `app/Services/CRM/Communication/Telephony/KnowlarityProvider.php`

### Task F4-05 — Voice + IVR Services
```php
// app/Services/CRM/Communication/VoiceService.php

// BRD: CRM-CC-017 — Initiate outbound call from lead record
initiateClickToCall(Lead, User $counsellor): CallLog

// BRD: CRM-CC-018 — Finalise call log with outcome
finaliseCallLog(CallLog, array $outcome): CallLog

// BRD: CRM-CC-018 — Attach recording URL (only if consent given)
attachRecording(CallLog, User $requester): CallLog  // checks call_consent_given

// BRD: CRM-CC-016 — Handle provider webhook event
handleProviderEvent(array $payload, string $provider): void

// app/Services/CRM/Communication/IvrService.php

// BRD: CRM-LC-010 — Process inbound IVR call → create lead
handleInboundIvrCall(array $payload, IvrConfig $config): Lead

// BRD: CRM-CC-019 — Save/update IVR configuration
saveConfig(array $data, int $institutionId): IvrConfig
```

### Task F4-06 — LC-010: IVR Inbound Lead Auto-Creation

```
Inbound call to virtual number →
  Telephony provider webhook fires to /api/v1/crm/webhooks/ivr/{provider}
  1. Verify IP allowlist + shared secret
  2. ACK 200 OK immediately
  3. Dispatch ProcessIvrLeadCreationJob
  4. Job:
     a. Check if caller number matches existing lead (institution-scoped)
     b. No match → auto-create lead:
           LeadSource::IVR
           consent_given = false  (DPDP: verbal consent to be confirmed by counsellor)
           status = NEW_ENQUIRY
           phone = caller_number (encrypted)
           Dispatch DetectLeadDuplicatesJob
           Fire IvrLeadCreatedEvent
     c. Create CallLog record with direction = INBOUND, status = COMPLETED
     d. Notify fallback_counsellor_id from IvrConfig
```

### Task F4-07 — Controllers
```
app/Http/Controllers/CRM/Web/Communication/CallLogWebController.php
app/Http/Controllers/CRM/Web/Communication/IvrConfigWebController.php
app/Http/Controllers/CRM/Api/Webhooks/TelephonyWebhookController.php  ← API only
app/Http/Controllers/CRM/Api/Webhooks/IvrWebhookController.php        ← API only
```

### Task F4-08 — Events + Jobs
Events: `CallInitiatedEvent` · `CallCompletedEvent` · `CallLoggedEvent` · `IvrLeadCreatedEvent` · `MissedCallReceivedEvent`  
Jobs: `ProcessOutboundCallJob` · `ProcessTelephonyWebhookJob` · `ProcessIvrLeadCreationJob`  
Listeners: `LogCallToActivityTimeline` · `NotifyCounsellorOnMissedCall`

---

## 7. Implementation Tasks — Sub-Group F5 (Unified Inbox + Notifications)

### Task F5-01 — UnifiedInboxService
```php
// app/Services/CRM/Communication/UnifiedInboxService.php

// BRD: CRM-CC-021 — Fetch paginated inbound messages across all channels for assigned counsellor
getInboxForCounsellor(User $counsellor, array $filters): LengthAwarePaginator

// BRD: CRM-CC-021 — Mark message thread as read
markAsRead(string $channel, string $entityId, User $user): void

// BRD: CRM-CC-021 — Get unread count per channel
getUnreadCounts(User $counsellor): array  // returns [ 'email' => 3, 'whatsapp' => 7, 'sms' => 0 ]
```

### Task F5-02 — Livewire: UnifiedInbox
- `app/Livewire/CRM/Communication/UnifiedInbox.php` — polls every 10s, tabs per channel, unread badges
- View: `resources/views/livewire/crm/communication/unified-inbox.blade.php`

### Task F5-03 — Notifications (CC-023)
- `app/Notifications/CRM/InboundEmailNotification.php`
- `app/Notifications/CRM/InboundWhatsAppNotification.php`
- `app/Notifications/CRM/MissedCallNotification.php`

All notifications:
- `via()` returns `['database', 'mail']`
- Mobile push stub (Phase 2 — React Native)
- No PII in notification `toArray()` payload

### Task F5-04 — Service Provider
- `app/Providers/CRM/CrmCommunicationServiceProvider.php`
  - Bind all 6 repository interfaces
  - Bind 3 gateway interfaces (SMS) to resolved implementation via `IntegrationCredential`
  - Bind 3 BSP interfaces (WA) similarly
  - Register event → listener bindings (13 pairs)
  - Register policy: `CommunicationTemplatePolicy`

---

## 8. API Resources (External / Mobile Only)

- `app/Http/Resources/CRM/CommunicationTemplateResource.php`
- `app/Http/Resources/CRM/CommunicationLogResource.php`
- `app/Http/Resources/CRM/CallLogResource.php`
- `app/Http/Resources/CRM/WhatsAppConversationResource.php`
- `app/Http/Resources/CRM/WhatsAppMessageResource.php`

---

## 9. Security Controls

| Control | Implementation |
|---------|---------------|
| Webhook signature verification | `hash_equals(hmac_sha256($body, $secret), $header)` — all providers |
| No PII in logs | All `Log::*` calls use structured keys, never phone/email values |
| Call recording gated by consent | `call_consent_given` checked in `VoiceService::attachRecording()` |
| Campaign execution RBAC | `Gate::authorize('crm.campaigns.send', $campaign)` |
| Inbox access scoped to counsellor | All inbox queries filter on `assigned_user_id = auth()->id()` unless manager role |
| WhatsApp lead consent | `consent_given = false` on auto-creation; counsellor must confirm verbal consent |
| SMS DNC enforcement | `dnc_at` checked in `SmsService::sendToLead()` — throws `LeadDncException` if set |
| Unsubscribe within 24h | `EnforceUnsubscribeJob` dispatched on `LeadUnsubscribedEvent` |
| Gateway credentials | Fetched from `integration_credentials` (AES-256); never hardcoded |
| SPF/DKIM verification | `SenderDomain::dkim_verified` checked before campaign dispatch |

---

## 10. File Count Summary

| Layer | New Files | Modified Files |
|-------|-----------|----------------|
| Migrations | 12 | 0 |
| Enums | 14 | 1 (`ActivityType` + new types) |
| Models | 8 | 1 (`Lead` — unsubscribe fields) |
| DTOs | 9 | 0 |
| Repository interfaces + impl | 14 | 0 |
| Services | 8 | 0 |
| Gateway / BSP adapters | 9 | 0 |
| Events | 14 | 0 |
| Listeners | 10 | 0 |
| Jobs | 10 | 0 |
| Notifications | 3 | 0 |
| Policies | 1 | 0 |
| Web Controllers | 8 | 1 (`LeadWebController` — compose from lead) |
| API Webhook Controllers | 5 | 0 |
| API Resources | 5 | 0 |
| Form Requests | 12 | 0 |
| Livewire Components | 4 | 0 |
| Blade Views | 20 | 3 (lead show, dashboard, sidebar) |
| Service Providers | 1 | 2 (`AppServiceProvider`, `bootstrap/providers.php`) |
| Routes | 0 | 2 (`web.php` +15 routes, `api.php` +5 webhook routes) |
| **Total** | **~157** | **~10** |

---

## 11. Test Plan — 80 Tests Target

### F1 — Email Tests (20 tests)

**File:** `tests/Feature/CRM/Communication/EmailCommunicationTest.php`

| # | Test | Assertion |
|---|------|-----------|
| F1-T01 | `test_can_create_email_template` | Template stored with correct channel, merge tags returned |
| F1-T02 | `test_cannot_create_template_without_channel` | Validation returns 422 |
| F1-T03 | `test_template_renders_merge_tags_correctly` | `{{first_name}}` replaced with lead first name |
| F1-T04 | `test_template_scoped_to_institution` | Different institution cannot read template |
| F1-T05 | `test_can_send_individual_email_to_lead` | `CommunicationLog` created, `EmailSentEvent` fired |
| F1-T06 | `test_send_email_respects_dpdp_unsubscribe` | Throws exception when `email_unsubscribed_at` is set |
| F1-T07 | `test_send_email_respects_dnc` | SMS DNC check separate; email check own flag |
| F1-T08 | `test_email_campaign_fan_out_dispatches_jobs` | `Bus::assertDispatched(SendBulkEmailJob::class, $expectedCount)` |
| F1-T09 | `test_email_campaign_skips_unsubscribed_leads` | Job count = recipients minus unsubscribed |
| F1-T10 | `test_webhook_delivery_event_updates_log` | POST to `/api/v1/crm/webhooks/email/mailgun` → `delivered_at` set on log |
| F1-T11 | `test_webhook_open_event_updates_log` | `opened_at` set on log |
| F1-T12 | `test_webhook_bounce_event_increments_bounce_count` | `email_bounce_count` +1 on lead |
| F1-T13 | `test_webhook_rejects_invalid_signature` | 403 response when HMAC mismatch |
| F1-T14 | `test_webhook_acks_immediately_dispatches_job` | Response 200 before job runs |
| F1-T15 | `test_unsubscribe_sets_flag_within_24h` | `EnforceUnsubscribeJob` dispatched, `email_unsubscribed_at` set |
| F1-T16 | `test_unsubscribe_is_idempotent` | Second unsubscribe does not error |
| F1-T17 | `test_sender_domain_verified_flag_set` | After DNS check passes, `dkim_verified = true` |
| F1-T18 | `test_campaign_dispatch_blocked_without_verified_domain` | Exception raised |
| F1-T19 | `test_email_activity_logged_to_lead_timeline` | `ActivityType::EMAIL_SENT` entry created on lead's activities |
| F1-T20 | `test_email_log_has_no_pii` | `body_preview` does not contain mobile number or email |

### F2 — SMS Tests (15 tests)

**File:** `tests/Feature/CRM/Communication/SmsCommunicationTest.php`

| # | Test | Assertion |
|---|------|-----------|
| F2-T01 | `test_can_create_dlt_template` | Stored with DRAFT status |
| F2-T02 | `test_dlt_template_submission_changes_status` | Status → PENDING_APPROVAL |
| F2-T03 | `test_dlt_template_approval` | Status → APPROVED, dlt_template_id saved |
| F2-T04 | `test_sms_blocked_for_unapproved_dlt_template` | Exception when sending with DRAFT template |
| F2-T05 | `test_can_send_individual_sms_via_msg91` | HTTP call mocked, `CommunicationLog` created |
| F2-T06 | `test_sms_blocked_for_dnc_lead` | `LeadDncException` thrown |
| F2-T07 | `test_sms_blocked_for_unsubscribed_lead` | Throws when `sms_unsubscribed_at` set |
| F2-T08 | `test_sms_campaign_fan_out_dispatches_jobs` | `Bus::assertDispatched(SendBulkSmsJob::class)` |
| F2-T09 | `test_delivery_receipt_webhook_updates_log` | `delivered_at` set on `CommunicationLog` |
| F2-T10 | `test_sms_webhook_rejects_invalid_signature` | 403 on HMAC mismatch |
| F2-T11 | `test_sms_activity_logged_to_timeline` | `ActivityType::SMS_SENT` entry on lead |
| F2-T12 | `test_opt_out_sets_sms_and_dnc_flags` | Both `sms_unsubscribed_at` + `dnc_at` set |
| F2-T13 | `test_gateway_strategy_switches_by_credential` | Msg91 vs Kaleyra resolved correctly |
| F2-T14 | `test_sms_body_respects_dlt_template_format` | Message rendered matches DLT format |
| F2-T15 | `test_sms_log_body_preview_strips_pii` | `body_preview` has no raw mobile |

### F3 — WhatsApp Tests (20 tests)

**File:** `tests/Feature/CRM/Communication/WhatsAppCommunicationTest.php`

| # | Test | Assertion |
|---|------|-----------|
| F3-T01 | `test_inbound_whatsapp_creates_new_lead` | Lead created with `LeadSource::WHATSAPP`, `consent_given = false` |
| F3-T02 | `test_inbound_whatsapp_matches_existing_lead` | No duplicate lead created; conversation linked |
| F3-T03 | `test_inbound_whatsapp_deduplication_by_phone` | `DetectLeadDuplicatesJob` dispatched |
| F3-T04 | `test_whatsapp_lead_creation_is_institution_scoped` | Lead tied to correct institution from IVR config |
| F3-T05 | `test_webhook_verifies_meta_hub_signature` | 403 on invalid X-Hub-Signature-256 |
| F3-T06 | `test_webhook_acks_200_before_job_runs` | Response returns before job completes |
| F3-T07 | `test_can_send_template_message` | BSP mock called, `CommunicationLog` created |
| F3-T08 | `test_template_message_logged_to_activity_timeline` | `ActivityType::WHATSAPP_SENT` |
| F3-T09 | `test_delivery_status_webhook_updates_message` | `delivered_at` set on `WhatsAppMessage` |
| F3-T10 | `test_read_status_webhook_updates_message` | `read_at` set |
| F3-T11 | `test_can_send_session_message_from_inbox` | Free-text message sent, stored as OUTBOUND |
| F3-T12 | `test_inbox_only_shows_assigned_conversations` | Counsellor A cannot see counsellor B's conversations |
| F3-T13 | `test_wa_broadcast_dispatches_job_per_lead` | N leads → N `SendBulkWhatsAppJob` dispatches |
| F3-T14 | `test_wa_broadcast_scoped_to_institution` | Leads from other institution excluded |
| F3-T15 | `test_inbound_message_notifies_assigned_counsellor` | `NotifyInboundMessageJob` dispatched |
| F3-T16 | `test_consent_given_false_on_auto_created_wa_lead` | DPDP: consent not assumed from WA initiation |
| F3-T17 | `test_counsellor_can_mark_conversation_resolved` | Status → RESOLVED |
| F3-T18 | `test_wa_message_body_is_encrypted_in_db` | Raw DB value differs from cleartext |
| F3-T19 | `test_bsp_adapter_dispatched_for_correct_provider` | Meta vs Interakt resolved by credential |
| F3-T20 | `test_inbound_whatsapp_fires_whatsapp_lead_created_event` | `Event::assertDispatched(WhatsAppLeadCreatedEvent::class)` |

### F4 — Voice / IVR Tests (15 tests)

**File:** `tests/Feature/CRM/Communication/VoiceCommunicationTest.php`

| # | Test | Assertion |
|---|------|-----------|
| F4-T01 | `test_ivr_inbound_creates_new_lead` | Lead with `LeadSource::IVR` created |
| F4-T02 | `test_ivr_inbound_matches_existing_lead` | Existing lead's call log updated |
| F4-T03 | `test_ivr_lead_creation_consent_given_false` | DPDP: verbal consent not captured via IVR |
| F4-T04 | `test_ivr_webhook_verifies_ip_allowlist` | 403 from non-allowlisted IP |
| F4-T05 | `test_ivr_webhook_acks_200_before_job` | Job dispatch verified; response returned immediately |
| F4-T06 | `test_click_to_call_creates_call_log` | `CallLog` created with direction = OUTBOUND, status = INITIATED |
| F4-T07 | `test_call_log_finalised_with_disposition` | `disposition` updated, `ended_at` set |
| F4-T08 | `test_recording_url_attached_only_with_consent` | `recording_url` null when `call_consent_given = false` |
| F4-T09 | `test_recording_url_attached_when_consent_given` | S3 URL stored after provider webhook fires |
| F4-T10 | `test_call_logged_to_activity_timeline` | `ActivityType::CALL_LOGGED` entry on lead |
| F4-T11 | `test_missed_call_notifies_fallback_counsellor` | `NotifyCounsellorOnMissedCall` dispatched |
| F4-T12 | `test_call_log_phone_numbers_encrypted` | Raw DB values differ from cleartext numbers |
| F4-T13 | `test_telephony_provider_resolved_by_credential` | Exotel vs Ozonetel resolved correctly |
| F4-T14 | `test_call_log_institution_scoped` | `institution_id` matches authenticated user's institution |
| F4-T15 | `test_ivr_config_requires_active_integration_credential` | Exception when credential inactive |

### F5 — Unified Inbox + Notifications (10 tests)

**File:** `tests/Feature/CRM/Communication/UnifiedInboxTest.php`

| # | Test | Assertion |
|---|------|-----------|
| F5-T01 | `test_inbox_aggregates_email_whatsapp_and_sms` | All 3 channels appear in inbox response |
| F5-T02 | `test_inbox_scoped_to_assigned_counsellor` | Counsellor A sees only their leads |
| F5-T03 | `test_inbox_manager_sees_full_team` | Manager with `crm.inbox.all` gate sees all |
| F5-T04 | `test_unread_counts_correct_per_channel` | Returns `['email' => 2, 'whatsapp' => 1, 'sms' => 0]` |
| F5-T05 | `test_mark_as_read_decrements_unread_count` | Count reduced after read |
| F5-T06 | `test_inbound_email_dispatches_notification_job` | `NotifyInboundMessageJob` dispatched |
| F5-T07 | `test_inbound_whatsapp_dispatches_notification_job` | `NotifyInboundMessageJob` dispatched |
| F5-T08 | `test_notification_has_no_pii_in_payload` | `toArray()` contains no mobile/email raw values |
| F5-T09 | `test_notification_sent_via_database_channel` | `Notification::assertSentOnDatabase` |
| F5-T10 | `test_notification_not_sent_for_own_outbound_message` | Counsellor not notified for their own sends |

---

## 12. Queue Configuration Additions (`config/horizon.php`)

Add supervisors for new queues:

```php
'crm-comms-email' => [
    'connection' => 'redis',
    'queue' => ['crm-comms-email'],
    'balance' => 'auto',
    'minProcesses' => 2,
    'maxProcesses' => 10,
    'tries' => 3,
    'timeout' => 90,
    'memory' => 256,
],
'crm-comms-whatsapp' => [
    'connection' => 'redis',
    'queue' => ['crm-comms-whatsapp'],
    'balance' => 'auto',
    'minProcesses' => 3,
    'maxProcesses' => 15,
    'tries' => 3,
    'timeout' => 60,
],
'crm-comms-sms' => [
    'connection' => 'redis',
    'queue' => ['crm-comms-sms'],
    'balance' => 'auto',
    'minProcesses' => 2,
    'maxProcesses' => 8,
    'tries' => 3,
    'timeout' => 30,
],
'crm-comms-voice' => [
    'connection' => 'redis',
    'queue' => ['crm-comms-voice'],
    'balance' => 'auto',
    'minProcesses' => 2,
    'maxProcesses' => 6,
    'tries' => 2,
    'timeout' => 30,
],
```

---

## 13. Dependencies and Prerequisites

| Dependency | Purpose | Package / Setup |
|------------|---------|-----------------|
| Mailgun / Postmark | Email delivery + webhooks | `symfony/mailgun-mailer` or `postmark/postmark-php` |
| MSG91 SDK | SMS gateway | HTTP client (no official Laravel pkg — use `guzzlehttp/guzzle`) |
| Meta Cloud API | WhatsApp BSP | HTTP client + `X-Hub-Signature-256` verification |
| Exotel SDK | Cloud telephony | HTTP client + IP allowlist |
| `laravel/horizon` | Queue management | Already installed |
| `spatie/laravel-permission` | RBAC gates | Already installed |
| Redis Stream | Real-time WA inbox | Already configured |
| S3 (ap-south-1) | Recording + media storage | Already configured in `filesystems.php` |

---

## 14. DPDP Compliance Checklist for Group F

- [ ] Email unsubscribe sets `email_unsubscribed_at` within 24h via `EnforceUnsubscribeJob`
- [ ] SMS opt-out sets `sms_unsubscribed_at` + `dnc_at` within 24h
- [ ] WhatsApp `consent_given` remains `false` on auto-created leads until counsellor confirms
- [ ] Call recording only stored when `call_consent_given = true`
- [ ] No raw phone/email in `Log::*`, notification payloads, or `body_preview` columns
- [ ] `wa_phone_number`, `from_number`, `to_number` are encrypted in database columns
- [ ] All communication mutations logged to `audit_logs` table
- [ ] Unsubscribe operations are idempotent (second unsubscribe = no error, no duplicate log)
- [ ] Campaign sends check consent + unsubscribe before dispatching each job
- [ ] IVR lead `consent_given = false` — counsellor must explicitly mark consent after verbal confirmation
