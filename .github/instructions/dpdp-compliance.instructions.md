---
description: "Use when writing any code that touches personal data, consent, opt-out, data erasure, call recording, SMS, PII fields, or data storage location. Enforces DPDP Act 2023 compliance rules. Critical for lead creation, communication, document handling, analytics, and any feature processing student personal information."
---

# DPDP Act 2023 Compliance Rules — A2A-CRM

## Non-Negotiable Requirements

These rules are derived from BRD compliance requirements CRM-CR-001 through CRM-CR-010.

---

## 1. Consent at Lead Capture (CRM-CR-001, CRM-CR-002)

Every lead creation — regardless of source (web form, API, import, walk-in) — MUST record:

```php
// BRD: CRM-CR-001 — Consent mandatory at point of lead creation
Schema::table('leads', function (Blueprint $table): void {
    $table->boolean('consent_given')->default(false);
    $table->timestamp('consent_timestamp')->nullable();
    $table->string('consent_ip', 45)->nullable();     // IPv4 or IPv6
    $table->string('consent_form_version')->nullable(); // e.g. "v2.1-2026-04"
});

// In CreateLeadRequest — consent_given: true is mandatory for all new leads
'consent_given' => ['required', 'accepted'],
```

For **bulk CSV imports**, consent_source must be documented in the import metadata. Cannot import leads without consent record.

---

## 2. No PII in Application Logs (CRM-CR-002)

```php
// ❌ PROHIBITED
Log::info('Processing lead for ' . $lead->mobile);
Log::info('Email sent to ' . $lead->email);

// ✅ CORRECT — use UUIDs/IDs only in logs
Log::info('Processing lead', ['lead_uuid' => $lead->uuid]);
Log::info('Communication dispatched', ['activity_log_id' => $activityLog->id]);
```

PII fields that MUST NOT appear in logs: `name`, `mobile`, `email`, `aadhaar_number`, `dob`, `address`

---

## 3. Encryption at Rest (CRM-CR-006, NFR-SE-002)

PII columns use Laravel's encrypted cast (AES-256-CBC via `APP_KEY`):

```php
// BRD: NFR-SE-002 — AES-256 encryption for PII at rest
protected $casts = [
    'mobile'          => 'encrypted',
    'email'           => 'encrypted',
    'aadhaar_number'  => 'encrypted',
    'date_of_birth'   => 'encrypted',
    'address_line_1'  => 'encrypted',
];
```

Documents stored in AWS S3 (ap-south-1 only) with server-side encryption (`AES256`). Signed URLs only — never public S3 URLs for documents.

---

## 4. Data in India (CRM-CR-006)

```php
// Storage config must ALWAYS specify ap-south-1 (Mumbai)
// .env
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET_REGION=ap-south-1

// In S3 config — NEVER replicate to non-India regions
's3' => [
    'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
    // cross_region_replication: PROHIBITED
]
```

Redis caches containing PII-adjacent data: also on same VPC in ap-south-1.

---

## 5. Opt-Out / Unsubscribe (CRM-CR-003)

```php
// BRD: CRM-CR-003 — Opt-out honoured within 24h, idempotent
class ProcessOptOutJob implements ShouldQueue
{
    public function handle(): void
    {
        $lead = Lead::withoutGlobalScope(InstitutionScope::class)
            ->where('uuid', $this->leadUuid)
            ->firstOrFail();

        // Idempotent — safe to call multiple times
        $lead->update([
            'opt_out'    => true,
            'opt_out_at' => $lead->opt_out_at ?? now(),
            'opt_out_channel' => $this->channel,
        ]);

        // Remove from all active nurture sequences
        AutomationEnrolment::where('lead_id', $lead->id)->delete();
    }
}

// Before EVERY communication dispatch — check opt-out
if ($lead->opt_out) {
    throw new LeadOptedOutException("Lead {$lead->uuid} has opted out.");
}
```

---

## 6. Right to Erasure — Anonymise, Never Hard Delete (CRM-CR-005)

```php
// BRD: CRM-CR-005 — Erasure anonymises PII within 30 days, preserves aggregate analytics
class AnonymisePIIJob implements ShouldQueue
{
    public function handle(): void
    {
        $lead = Lead::withoutGlobalScope(InstitutionScope::class)
            ->findOrFail($this->leadId);

        $anonymisedId = 'ANON-' . Str::random(12);

        $lead->update([
            'first_name' => 'Anonymised',
            'last_name'  => 'User',
            'mobile'     => $anonymisedId,   // deterministic placeholder
            'email'      => $anonymisedId . '@anonymised.invalid',
            'aadhaar_number' => null,
            'address_line_1' => null,
            'pii_anonymised_at' => now(),
            'pii_anonymised_reason' => $this->reason, // erasure_request | retention_expiry
        ]);

        // Documents: delete from S3 + null storage path
        foreach ($lead->documents as $doc) {
            Storage::disk('s3')->delete($doc->storage_path);
            $doc->update(['storage_path' => null, 'anonymised' => true]);
        }

        // audit_logs, activity_logs: preserve (no PII in these by design)
    }
}
```

---

## 7. Call Recording Consent (CRM-CR-007)

```php
// BRD: CRM-CR-007 — Recording PROHIBITED without explicit consent
public function startRecording(Call $call): void
{
    if (! $call->lead->call_consent_given) {
        throw new CallRecordingConsentRequiredException(
            'Cannot record call without explicit consent from lead ' . $call->lead->uuid
        );
    }
    // Proceed with recording
}
```

---

## 8. SMS DLT Compliance (CRM-CR-008)

```php
// Every SMS must use a DLT-registered template
// BRD: CRM-CR-008 — TRAI DLT-registered templates only
class SmsTemplate extends Model
{
    // dlt_template_id (registered with TRAI DLT portal) is REQUIRED
    protected $fillable = ['name', 'body', 'dlt_template_id', 'dlt_sender_id', ...];
}

// Validation in SmsSendRequest
'dlt_template_id' => ['required', 'string', 'exists:sms_templates,dlt_template_id'],
```

---

## 9. AI Usage Audit Trail (CRM-AI-012)

Every call to `AnthropicService` MUST be logged:

```php
AiUsageLog::create([
    'feature_code'    => 'CRM-AI-002',    // BRD Req ID
    'institution_id'  => $institutionId,
    'user_id'         => Auth::id(),
    'lead_uuid'       => $lead->uuid,     // no raw PII
    'model_version'   => $response->model,
    'prompt_tokens'   => $response->usage->input_tokens,
    'completion_tokens' => $response->usage->output_tokens,
    'latency_ms'      => $latencyMs,
    'timestamp'       => now(),
]);
```

---

## Summary Checklist

Before merging any feature touching personal data:

- [ ] `consent_given` + timestamp captured at lead/applicant creation
- [ ] No PII fields appear in `Log::*()` calls
- [ ] PII database columns use `'encrypted'` cast
- [ ] S3 region is `ap-south-1`
- [ ] `opt_out` check before every communication dispatch
- [ ] Hard delete prohibited — `AnonymisePIIJob` used for erasure
- [ ] `call_consent_given` guard on any call recording
- [ ] `dlt_template_id` present on all SMS templates
- [ ] AI calls logged to `ai_usage_logs`
- [ ] No PII sent to Anthropic API
