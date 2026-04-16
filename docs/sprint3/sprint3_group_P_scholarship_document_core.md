# Sprint 3 - Group P: Scholarships and Document Core

**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Group:** P  
**Modules:** Fee and Payments + Document Management  
**Req IDs:** CRM-FM-006 to CRM-FM-009, CRM-DM-001 to CRM-DM-005, CRM-DM-008 to CRM-DM-010  
**Status:** Planned

---

## Objective

Deliver scholarship and waiver workflows plus core document collection, verification, and tracking.

## In Scope

1. Scholarship and fee waiver category setup.
2. Eligibility rule evaluation framework.
3. Approval workflow chain for fee discounts and waivers.
4. Installment plan support for initial fee.
5. Programme-wise document checklist management.
6. Multi-channel document upload entry points.
7. Document status lifecycle and reviewer actions.
8. Automated pending document reminders.
9. Encrypted document storage and role-based access.
10. Bulk document download controls.
11. Document completeness score visibility.

## Dependencies

1. Group O payment and scholarship context.
2. Existing DM-006 and DM-007 integration foundations from Sprint 2.
3. Notification engine for reminder workflows.

## Design Notes

1. Keep verification status transitions auditable.
2. Enforce encryption at rest and strict access controls.
3. Treat checklist definitions as institution-scoped configuration.
4. Include rejection reason capture and re-upload paths.

## Deliverables

1. Group implementation log updates.
2. User manual section for admissions and document verification teams.
3. Group P test cases document.
4. Master tracker status and remarks update.

## Acceptance Gates

1. Scholarship approval flow works end-to-end.
2. Document checklist and submission tracking are operational.
3. Reviewers can approve, reject, and comment on documents.
4. Completeness score reflects checklist status accurately.

## Risks and Mitigation

1. Large file handling and storage cost:
Mitigation: file-size policy, compression where valid, lifecycle rules.
2. Verification backlog:
Mitigation: SLA dashboards and queue-based reminder escalations.

## Exit Criteria

1. FM-006 to FM-009 and DM core requirements marked completed in tracker.
2. User manual and test cases published.
3. QA and compliance sign-off recorded.
