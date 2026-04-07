---
description: "Generate a complete Pest PHP feature test suite for a given A2A-CRM service, controller, or API endpoint. Covers happy path, validation failures, multi-tenancy isolation, DPDP compliance, queue assertions, and RBAC gate checks."
agent: "agent"
tools: [read, search, edit]
argument-hint: "Service or endpoint to test, e.g. 'LeadService::create' or 'POST /api/v1/crm/leads'"
---

Generate a comprehensive Pest PHP test suite for the specified A2A-CRM component.

## Test Coverage Requirements

Achieve ≥70% line coverage (BRD: NFR-MT-004). Include ALL of the following categories:

### 1. Happy Path Tests
- Successful creation/update/retrieval
- Correct response structure (standard envelope: `success`, `data`, `message`, `meta`)
- UUID returned, never `id` or `institution_id`
- Correct HTTP status codes

### 2. Validation Tests
- Required field missing
- Invalid field format
- `consent_given` absent or false (for lead/applicant creation)
- Returns `422` with `errors` array

### 3. Multi-Tenancy Isolation Tests (CRITICAL)
- Cannot access records belonging to another institution
- Returns `404` (not `403`) to avoid information disclosure
- Counsellor cannot see leads assigned to other counsellors

### 4. RBAC Gate Tests
- Unauthenticated request returns `401`
- Wrong role returns `403`
- Correct role can access

### 5. DPDP Compliance Tests
- PII not present in application logs after operation
- `consent_given` validated at lead creation
- `opt_out = true` prevents communication dispatch
- `AnonymisePIIJob` tested for completeness

### 6. Queue Assertions
- Async operations (AI scoring, ERP sync, bulk comms) dispatched to queue, not executed synchronously
- `Queue::assertPushed()` for each expected job

### 7. Event Assertions
- Domain events fired on state changes
- `Event::assertDispatched()` for key events

### 8. Edge Cases
- Duplicate detection scenarios
- Concurrent status updates
- Gateway/external service failure handling (mock failures)

## Output Format

Generate:
1. Full test file at `tests/Feature/CRM/{Module}/{FeatureName}Test.php`
2. Required factory states if not already documented
3. List of test case names as a quick reference
4. Commands to run: `./vendor/bin/pest tests/Feature/CRM/{Module}/` and coverage check
