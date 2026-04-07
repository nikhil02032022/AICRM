---
name: a2a-erp-integration
description: "Build or review integration points between A2A-CRM and the A2A ERP ecosystem. Use when implementing Programme Master sync, Fee module integration, Student Master conversion, seat availability, alumni bridge, LMS enrolment trigger, or any ERP API contract. Trigger: ERP integration, Student Master, Programme Master, convertToStudent, seat availability, alumni bridge, LMS auto-enrol."
argument-hint: "Integration point to implement (e.g. 'Programme Master sync from ERP', 'Student Master conversion field mapping')"
---

# A2A ERP Integration

Builds and validates integration points between A2A-CRM and the A2A ERP platform (BRD section 8.17, CRM-EI-001 through CRM-EI-010).

## When to Use

- Implementing a CRM → ERP data write (Student Master conversion, fee accounting)
- Implementing an ERP → CRM data pull (Programme Master, Fee structures, seat availability)
- Designing the field mapping for lead-to-student conversion
- Reviewing an ERP integration for idempotency and circuit breaker patterns

## Integration Contracts

### ERP API Client Configuration

```php
// All ERP calls through ErpApiClient — never direct HTTP::post()
// Base URL: configured per institution in integration_credentials
// Auth: server-to-server API key (encrypted in integration_credentials)
// Timeout: 10s | Retry: 3x with exponential backoff | Circuit breaker: open after 3 consecutive failures
```

### CRM-EI-001 — Programme Master Sync

**Direction:** ERP → CRM  
**Trigger:** Scheduled (hourly) + webhook from ERP on programme change  
**Idempotency:** Upsert on `erp_programme_code`

```
GET /api/v1/erp/programmes?institution_id={id}&updated_after={timestamp}
Response fields: programme_code, name, duration_months, intake_capacity,
                 eligibility_criteria, fee_structure_id, academic_year
```

### CRM-EI-002 — Fee Structure Sync

**Direction:** ERP → CRM  
**Trigger:** On-demand + scheduled daily  
**Cache:** Redis `fee_structure:{institution_id}:{programme_id}` TTL 1h

### CRM-EI-004 — Seat Availability

**Direction:** ERP → CRM (read-only)  
**Trigger:** Every 5 minutes via `RefreshSeatAvailabilityJob`  
**Cache:** Redis `seat_avail:{institution_id}:{programme_id}:{cycle_id}` TTL 6min  
**Real-time:** WebSocket push via `SeatAvailabilityUpdatedEvent` when threshold crossed

### CRM-EI-005 — Lead → Student Master Conversion (Critical)

**Direction:** CRM → ERP  
**Trigger:** Counsellor action after fee confirmation  
**ALWAYS async** — dispatched as `ConvertLeadToStudentJob`  
**Idempotency:** Guard on `erp_student_uuid` — skip if already converted

Complete field mapping:

| CRM Field | ERP Student Master |
|-----------|-------------------|
| `lead.first_name + last_name` | `student_name` |
| `lead.mobile` (decrypt) | `mobile_primary` |
| `lead.email` (decrypt) | `email` |
| `lead.academic_record_json` | `academic_history` |
| `application.programme_id` | `programme_code` (ERP code) |
| `application.admission_cycle_id` | `admission_cycle` |
| `crm_payments` (booking amount) | `fees.pre_admission_paid` |
| `documents[].storage_path` | `student_documents[]` |
| `lead.consent_given` | `data_consent.given` |
| `lead.consent_timestamp` | `data_consent.timestamp` |

### CRM-EI-006 — Fee Accounting Mirror

**Direction:** CRM → ERP Fee Module  
**Trigger:** `PaymentConfirmedEvent`  
**Data:** amount, gateway, gateway_payment_id, type, paid_at

### CRM-EI-008 — Alumni Bridge

**Direction:** CRM → A2A Alumni Module  
**Trigger:** `StudentGraduatedEvent` from ERP → `SyncAlumniRecordJob`

### CRM-EI-010 — LMS Auto-Enrolment

**Direction:** CRM event → ERP → CamPLUS/Moodle trigger  
**Trigger:** `LeadConvertedToStudentEvent` → ERP fires LMS enrolment

## Procedure

### Step 1 — Identify Integration Direction and Trigger

Determine: CRM→ERP or ERP→CRM? Event-driven, scheduled, or on-demand?

### Step 2 — Design the API Contract

Document:
- Endpoint URL pattern
- Request payload (field names, types)
- Response schema
- Error codes
- Rate limits

### Step 3 — Implement the Job

```php
final class {IntegrationName}Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 60, 120, 300, 600]; // exponential

    public function handle(ErpApiClient $client): void
    {
        // Idempotency check first
        // Call ERP API
        // Log to erp_sync_logs
        // Fire completion event
    }

    public function failed(\Throwable $e): void
    {
        // Alert institution admin after all retries exhausted
        // Log failure to erp_sync_logs
    }
}
```

### Step 4 — Add Circuit Breaker

```php
// ErpApiClient uses circuit breaker
// State: CLOSED (normal) → OPEN (failing) → HALF-OPEN (testing recovery)
// Open threshold: 3 consecutive failures
// Recovery probe: every 60 seconds
// When OPEN: jobs fail fast with ErpCircuitOpenException
//            CRM continues to function — ERP sync queues for later
```

### Step 5 — Test the Integration

- Mock `ErpApiClient` — never call real ERP in CI tests
- Test idempotency: calling twice produces same result
- Test circuit breaker: assert fast-fail on open circuit
- Test field mapping: every CRM field correctly mapped to ERP field

### Step 6 — Document the Integration

```markdown
## Integration: {Name}
**BRD:** CRM-EI-XXX
**Direction:** CRM→ERP | ERP→CRM
**Trigger:** Scheduled every Xmin | Event-driven | On-demand
**Idempotency Key:** {field name}
**ERP Endpoint:** /api/v1/erp/{path}
**Failure Handling:** {retry strategy}
**CRM Fallback:** {what CRM does if ERP is down}
```
