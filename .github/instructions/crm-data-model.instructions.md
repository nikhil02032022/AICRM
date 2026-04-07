---
description: "Use when writing database migrations, creating new Eloquent models, defining table schemas, adding indexes, or reviewing data model decisions for A2A-CRM. Covers the core CRM entity structure: Lead, Application, ActivityLog, Task, Document, Payment, Campaign, CrmProgramme, Agent, and their relationships."
applyTo: "database/migrations/**"
---

# A2A-CRM Core Data Model Standards

## Core Entity Schema Reference

### Leads Table (BRD Section 10.1)

```php
Schema::create('leads', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('institution_id');
    $table->unsignedBigInteger('campus_id')->nullable();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('mobile');           // encrypted at rest
    $table->string('email')->nullable();// encrypted at rest
    $table->string('source');           // google_ads|facebook|walk_in|referral|...
    $table->string('source_utm_params')->nullable(); // JSON: utm_campaign, utm_medium...
    $table->unsignedTinyInteger('lead_score')->default(0); // 0–100
    $table->string('temperature')->default('COLD'); // HOT|WARM|COLD|LOST|CONVERTED
    $table->string('status');           // LeadStatus enum value
    $table->unsignedBigInteger('assigned_counsellor_id')->nullable();
    // Consent — DPDP Act (BRD: CRM-CR-001)
    $table->boolean('consent_given')->default(false);
    $table->timestamp('consent_timestamp')->nullable();
    $table->string('consent_ip', 45)->nullable();
    $table->string('consent_form_version')->nullable();
    // Communication
    $table->boolean('opt_out')->default(false);
    $table->timestamp('opt_out_at')->nullable();
    $table->boolean('call_consent_given')->default(false);
    // Deduplication
    $table->string('erp_student_uuid')->nullable(); // set after CRM→ERP conversion
    $table->timestamp('pii_anonymised_at')->nullable(); // set by AnonymisePIIJob
    $table->timestamps();
    $table->softDeletes();

    // Mandatory indexes (BRD NFR + copilot-instructions)
    $table->index(['institution_id', 'campus_id']);
    $table->index('mobile');
    $table->index('email');
    $table->index('status');
    $table->index('lead_score');
    $table->index('assigned_counsellor_id');
    $table->index('created_at');
    $table->index('temperature');
    $table->index('source');
});
```

### Applications Table (BRD: CRM-AP-001)

```php
Schema::create('applications', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('institution_id');
    $table->unsignedBigInteger('campus_id')->nullable();
    $table->unsignedBigInteger('lead_id');            // FK leads.id
    $table->unsignedBigInteger('programme_id');       // FK crm_programmes.id
    $table->unsignedBigInteger('admission_cycle_id');
    $table->json('form_data');                        // application form answers
    $table->string('status');                         // ApplicationStatus enum
    $table->boolean('application_fee_paid')->default(false);
    $table->string('erp_student_uuid')->nullable();   // set on CRM-EI-005 conversion
    $table->timestamps();
    $table->softDeletes();

    $table->index(['institution_id', 'programme_id', 'status']);
    $table->index('lead_id');
    $table->index('admission_cycle_id');
});
```

### Activity Logs Table (BRD: CRM-EC-004, CRM-CC-022)

```php
// Polymorphic communication + activity log across ALL channels
Schema::create('activity_logs', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('institution_id');
    $table->unsignedBigInteger('lead_id');
    $table->string('loggable_type');   // polymorphic: Lead|Application|CrmProgramme
    $table->unsignedBigInteger('loggable_id');
    $table->string('channel');         // email|sms|whatsapp|voice|note|status_change|document|payment
    $table->string('direction');       // inbound|outbound|internal
    $table->string('status')->nullable(); // delivered|opened|clicked|bounced|failed
    $table->text('content_ref')->nullable(); // template_id or note text — NO raw PII content
    $table->unsignedBigInteger('user_id')->nullable(); // counsellor who performed action
    $table->timestamp('occurred_at');
    $table->timestamps();

    $table->index(['lead_id', 'occurred_at']);
    $table->index(['loggable_type', 'loggable_id']);
    $table->index('channel');
    $table->index('institution_id');
});
```

### Tasks Table (BRD: CRM-TF-001)

```php
Schema::create('tasks', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('institution_id');
    $table->unsignedBigInteger('lead_id');
    $table->unsignedBigInteger('assigned_to');  // user_id (counsellor)
    $table->string('type');             // call|email|whatsapp|meeting|document_review
    $table->string('status');           // pending|in_progress|completed|overdue
    $table->timestamp('due_at');
    $table->string('disposition')->nullable(); // outcome when completed
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['assigned_to', 'status', 'due_at']);
    $table->index(['lead_id', 'status']);
    $table->index('institution_id');
});
```

### Documents Table (BRD: CRM-DM-001)

```php
Schema::create('documents', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('institution_id');
    $table->morphs('documentable');    // polymorphic: Lead|Application
    $table->string('document_type');   // marksheet_10|marksheet_12|id_proof|...
    $table->string('status');          // not_submitted|submitted|under_review|verified|rejected
    $table->string('storage_path')->nullable(); // S3 key — encrypted
    $table->string('original_filename')->nullable();
    $table->text('verification_notes')->nullable();
    $table->unsignedBigInteger('verified_by')->nullable(); // user_id
    $table->timestamp('verified_at')->nullable();
    $table->boolean('anonymised')->default(false);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['documentable_type', 'documentable_id', 'status']);
    $table->index('institution_id');
});
```

### Payments Table (BRD: CRM-FM-001)

```php
Schema::create('crm_payments', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('institution_id');
    $table->unsignedBigInteger('lead_id');
    $table->unsignedBigInteger('application_id')->nullable();
    $table->string('type');            // application_fee|booking_amount|scholarship_waiver
    $table->decimal('amount', 10, 2);
    $table->string('gateway');         // razorpay|payu|ccavenue
    $table->string('gateway_order_id')->nullable();
    $table->string('gateway_payment_id')->nullable();
    $table->string('status');          // pending|paid|failed|refunded
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['lead_id', 'status']);
    $table->index('institution_id');
    $table->index('gateway_payment_id');
});
```

### Audit Logs Table (BRD NFR-SE, CRM-SA-004)

```php
// Captures every mutation to CRM data for compliance audit
Schema::create('audit_logs', function (Blueprint $table): void {
    $table->id();
    $table->string('entity_type');     // Lead|Application|Payment|...
    $table->string('entity_uuid');     // UUID of the affected record
    $table->unsignedBigInteger('institution_id');
    $table->string('action');          // created|updated|deleted|anonymised|exported
    $table->json('old_values')->nullable();  // no PII where possible
    $table->json('new_values')->nullable();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->timestamp('timestamp');

    $table->index(['entity_type', 'entity_uuid']);
    $table->index(['institution_id', 'timestamp']);
    $table->index('user_id');
});
```

## Migration Rules

1. Every `up()` MUST have a working `down()`.
2. Never drop columns in the same migration as a code deploy — two-step process.
3. Never add a `NOT NULL` column without a default to an existing table in production.
4. Index all foreign keys and all columns used in `WHERE` / `ORDER BY` clauses.
5. Use `$table->softDeletes()` on all core CRM entity tables.
6. Always use `$table->uuid('uuid')->unique()` — this is the external identifier.
