---
description: "Generate a complete, reversible Laravel database migration for an A2A-CRM entity table. Includes all required columns, mandatory indexes, institution/campus scoping, uuid, softDeletes, and encrypted PII fields where applicable."
agent: "agent"
tools: [read, search, edit]
argument-hint: "Entity name and list of columns needed, e.g. 'crm_campaigns: name, channel, target_segment, status, scheduled_at'"
---

Generate a complete Laravel migration for A2A-CRM following all project standards.

## Requirements

The migration MUST include:

1. **Standard columns** on every CRM table:
   - `$table->id()` — auto-increment PK (internal only)
   - `$table->uuid('uuid')->unique()` — external identifier
   - `$table->unsignedBigInteger('institution_id')` — tenant scope
   - `$table->unsignedBigInteger('campus_id')->nullable()` — campus scope
   - `$table->timestamps()`
   - `$table->softDeletes()` — REQUIRED, no hard deletes

2. **Encrypted PII columns** — If any of these fields are present, use `'encrypted'` cast in the Model:
   - `mobile`, `email`, `aadhaar_number`, `date_of_birth`, `address_*`

3. **Mandatory indexes**:
   - `['institution_id', 'campus_id']` composite
   - `status` if a status column exists
   - `assigned_to` / `assigned_counsellor_id` if present
   - `created_at`
   - Any column used in WHERE / ORDER BY operations

4. **Reversible `down()` method** — Always `Schema::dropIfExists('table_name')`

5. **DPDP consent columns** if this table captures personal data:
   - `consent_given` (boolean)
   - `consent_timestamp` (nullable timestamp)
   - `consent_ip` (varchar 45)
   - `consent_form_version` (varchar)

## Output Format

Provide:
1. Full migration file at `database/migrations/YYYY_MM_DD_HHMMSS_create_{table}_table.php`
2. Model skeleton at `app/Models/CRM/{ModelName}.php` with correct `$casts` for encrypted fields and enums
3. Brief BRD cross-reference: which BRD Req IDs this entity supports
4. List of indexes created and why each matters for query performance
