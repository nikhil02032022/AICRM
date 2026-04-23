# Sprint 4 Group U — Agent and Channel Partner Portal Test Cases

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** U
**Module:** Agent and Channel Partner Management
**Req IDs:** CRM-AG-001, CRM-AG-002, CRM-AG-003, CRM-AG-004, CRM-AG-005, CRM-AG-007
**Test Author:** Group U
**Date:** April 22, 2026

---

## Unit Tests

### TC-U-001 — Referral Code Generation Format

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-001 |
| **Req ID** | CRM-AG-002 |
| **Test File** | `tests/Unit/CRM/Agents/AgentReferralServiceTest.php` |
| **Preconditions** | Agent exists with institution name "Test College" |
| **Steps** | Call `AgentReferralService::generateCode($agent)` |
| **Expected Result** | Returns `AgentReferralCode` with code matching `/^TESTCO-AG-[0-9A-F]{4}$/` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-002 — Unique Referral Code Generation

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-002 |
| **Req ID** | CRM-AG-002 |
| **Test File** | `tests/Unit/CRM/Agents/AgentReferralServiceTest.php` |
| **Preconditions** | Two agents under same institution |
| **Steps** | Generate code for each agent |
| **Expected Result** | Both codes are unique |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-003 — Referral Code Resolution from ?ref Param

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-003 |
| **Req ID** | CRM-AG-002 |
| **Test File** | `tests/Unit/CRM/Agents/AgentReferralServiceTest.php` |
| **Preconditions** | AgentReferralCode exists with code `TESTCO-AG-ABCD` |
| **Steps** | Create `Request` with `?ref=TESTCO-AG-ABCD`; call `resolveFromRequest()` |
| **Expected Result** | Returns the correct Agent |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-004 — Lead Attribution Sets Agent and Source

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-004 |
| **Req ID** | CRM-AG-002 |
| **Test File** | `tests/Unit/CRM/Agents/AgentReferralServiceTest.php` |
| **Preconditions** | Agent with referral code; Lead with source=walk_in |
| **Steps** | Call `AgentReferralService::attributeLead($lead, $agent)` |
| **Expected Result** | `lead.agent_id = agent.id`; `lead.source = agent`; `referral_code.total_leads = 1` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-005 — PerEnrolment Commission Calculation

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-005 |
| **Req ID** | CRM-AG-005 |
| **Test File** | `tests/Unit/CRM/Agents/CommissionAccrualServiceTest.php` |
| **Preconditions** | Agent with PerEnrolment structure of ₹5,000; Application with agent-attributed lead |
| **Steps** | Call `CommissionAccrualService::accrue($application)` |
| **Expected Result** | Returns accrual with `commission_amount = 5000.00`; `accrual_basis_amount = 0` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-006 — PercentageFee Commission Calculation

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-006 |
| **Req ID** | CRM-AG-005 |
| **Test File** | `tests/Unit/CRM/Agents/CommissionAccrualServiceTest.php` |
| **Preconditions** | Agent with PercentageFee structure of 10%; Confirmed payment of ₹50,000 |
| **Steps** | Call `CommissionAccrualService::accrue($application)` |
| **Expected Result** | `commission_amount = 5000.00`; `accrual_basis_amount = 50000.00` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-007 — No Accrual When Lead Has No Agent

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-007 |
| **Req ID** | CRM-AG-005 |
| **Test File** | `tests/Unit/CRM/Agents/CommissionAccrualServiceTest.php` |
| **Preconditions** | Application whose lead has no `agent_id` |
| **Steps** | Call `CommissionAccrualService::accrue($application)` |
| **Expected Result** | Returns `null`; no accrual record created |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-008 — Report Aggregates Total Leads Correctly

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-008 |
| **Req ID** | CRM-AG-007 |
| **Test File** | `tests/Unit/CRM/Agents/AgentReportServiceTest.php` |
| **Preconditions** | 3 leads attributed to agent |
| **Steps** | Call `AgentReportService::forAgent($agent)` |
| **Expected Result** | `total_leads = 3` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-009 — Report Commission Breakdown by Status

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-009 |
| **Req ID** | CRM-AG-007 |
| **Test File** | `tests/Unit/CRM/Agents/AgentReportServiceTest.php` |
| **Preconditions** | Agent has pending accrual of ₹5,000 and paid accrual of ₹3,000 |
| **Steps** | Call `AgentReportService::forAgent($agent)` |
| **Expected Result** | `total_accrued_commission = 8000`; `pending_commission = 5000`; `paid_commission = 3000` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

## Feature Tests

### TC-U-010 — Agent CRUD: Create and Auto-Generate Referral Code

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-010 |
| **Req ID** | CRM-AG-001, CRM-AG-002 |
| **Test File** | `tests/Feature/CRM/Agents/AgentCrudTest.php` |
| **Preconditions** | Admissions manager user logged in |
| **Steps** | POST `/crm/agents` with valid name, email, password, agreement_start |
| **Expected Result** | Agent created; referral code auto-generated; redirect to index with success flash |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-011 — Agent CRUD: Institution Isolation

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-011 |
| **Req ID** | CRM-AG-001, NFR-MT-001 |
| **Test File** | `tests/Feature/CRM/Agents/AgentCrudTest.php` |
| **Preconditions** | Agent from Institution B exists; Manager from Institution A logs in |
| **Steps** | GET `/crm/agents` |
| **Expected Result** | Institution B agent does NOT appear in response |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-012 — Agent Deactivation

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-012 |
| **Req ID** | CRM-AG-001 |
| **Test File** | `tests/Feature/CRM/Agents/AgentCrudTest.php` |
| **Preconditions** | Active agent exists |
| **Steps** | DELETE `/crm/agents/{id}` |
| **Expected Result** | Agent `status` = `inactive`; soft-delete NOT applied |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-013 — Commission Structure: PerEnrolment Created Successfully

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-013 |
| **Req ID** | CRM-AG-004 |
| **Test File** | `tests/Feature/CRM/Agents/AgentCommissionStructureTest.php` |
| **Preconditions** | Agent and programme exist; manager logged in |
| **Steps** | POST `/crm/agents/{agent}/commission-structures` with structure_type=per_enrolment, amount=5000 |
| **Expected Result** | Record created with correct amount; redirect to index |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-014 — Commission Structure: Amount Required for PerEnrolment

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-014 |
| **Req ID** | CRM-AG-004 |
| **Test File** | `tests/Feature/CRM/Agents/AgentCommissionStructureTest.php` |
| **Preconditions** | Manager logged in |
| **Steps** | POST with `structure_type=per_enrolment`, no `amount` |
| **Expected Result** | Validation error on `amount` field |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-015 — Referral Attribution: Valid ?ref= Sets Agent on Lead

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-015 |
| **Req ID** | CRM-AG-002 |
| **Test File** | `tests/Feature/CRM/Agents/AgentReferralAttributionTest.php` |
| **Preconditions** | AgentReferralCode exists; Lead created |
| **Steps** | Call `attributeLead()` after resolving agent from valid ?ref= |
| **Expected Result** | `lead.agent_id` = correct agent ID; `lead.source = agent`; `total_leads` incremented |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-016 — Agent Portal: Unauthenticated Redirects to Login

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-016 |
| **Req ID** | CRM-AG-003 |
| **Test File** | `tests/Feature/CRM/Agents/AgentPortalLeadSubmitTest.php` |
| **Preconditions** | No agent session cookie |
| **Steps** | GET `/agent-portal/dashboard` |
| **Expected Result** | Redirect to `/agent-portal/login` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-017 — Agent Portal: Lead Submission Creates Record with agent_id

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-017 |
| **Req ID** | CRM-AG-003 |
| **Test File** | `tests/Feature/CRM/Agents/AgentPortalLeadSubmitTest.php` |
| **Preconditions** | Authenticated agent session |
| **Steps** | POST `/agent-portal/leads` with valid student details + consent |
| **Expected Result** | Lead created with `agent_id = authenticated agent ID`; source = agent |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

### TC-U-018 — Observer Fires Accrual on ENROLLED Transition

| Field | Value |
|-------|-------|
| **Test ID** | TC-U-018 |
| **Req ID** | CRM-AG-005 |
| **Test File** | `tests/Feature/CRM/Agents/EnrolmentCommissionAccrualTest.php` |
| **Preconditions** | Agent with PerEnrolment structure ₹7,500; Application in OFFER_ACCEPTED |
| **Steps** | Update application `status` to `enrolled` |
| **Expected Result** | 1 `AgentCommissionAccrual` record created with `commission_amount = 7500`; `status = pending` |
| **Actual Result** | ✅ Pass |
| **Status** | ✅ Pass |

---

## Summary

| Category | Total | Pass | Fail |
|----------|-------|------|------|
| Unit Tests | 9 | 9 | 0 |
| Feature Tests | 9 | 9 | 0 |
| **Total** | **18** | **18** | **0** |

---

## Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Developer | Group U | 2026-04-22 | ✅ |
| QA Reviewer | | | |
