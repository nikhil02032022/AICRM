# Sprint 3 - Group O: Fee Collection and Payments Core

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** O  
**Module:** Fee, Scholarship and Payment Management  
**Req IDs:** CRM-FM-001 to CRM-FM-005, CRM-FM-010 to CRM-FM-013  
**Status:** Planned

---

## Objective

Implement pre-admission payment operations from fee setup to collection tracking and ERP fee sync.

## In Scope

1. Application fee collection.
2. Seat reservation and booking fee support.
3. Gateway integration structure for Razorpay, PayU, and CCAvenue.
4. Payment link generation and sharing.
5. Payment confirmation auto-logging and status update trigger.
6. Automated reminder flows.
7. Fee dashboards for collected, pending, refunds, and forecast.
8. CRM-to-ERP fee record migration on enrolment conversion.

## Dependencies

1. Group M and Group N application lifecycle states.
2. Integration credentials and secure storage setup.
3. Queue workers for webhook and reminder processing.

## Design Notes

1. Use idempotent payment webhook handlers.
2. Keep gateway providers behind adapter interfaces.
3. Avoid logging any sensitive payment data.
4. Enforce institution and campus scoping in all fee queries.

## Deliverables

1. Group implementation log updates.
2. User manual section for counsellor and finance workflows.
3. Group O test cases document.
4. Master tracker status and remarks update.

## Acceptance Gates

1. Payment links are generated and traceable.
2. Successful and failed payment flows update status correctly.
3. Reminders trigger on schedule with opt-out compliance.
4. ERP fee handoff flow works for converted students.

## Risks and Mitigation

1. Gateway callback variance:
Mitigation: normalized callback mapping and signature verification.
2. Reconciliation mismatches:
Mitigation: daily reconciliation job and exception report.

## Exit Criteria

1. FM-001 to FM-005 and FM-010 to FM-013 marked completed in tracker.
2. User manual and test cases published.
3. QA and finance validation sign-off recorded.
