# Sprint 4 Group V — Analytics, Dashboards and Reporting
# Test Cases

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** V | **Module:** Analytics, Dashboards and Reporting
**Last Updated:** 2026-04-23

---

## AR-007 — Role-Based Dashboard Scope

| Scenario ID | Preconditions | Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| V-007-01 | User with `counsellor` role logged in | Call `DashboardScopeService::resolveScope($user)` | Returns `role=counsellor`, `counsellor_ids=[$user->id]` | As expected | ✅ Pass |
| V-007-02 | User with `admissions_manager` role | Call `resolveScope($user)` | Returns `role=manager`, `counsellor_ids=null` | As expected | ✅ Pass |
| V-007-03 | User with `admissions_director` role | Call `resolveScope($user)` | Returns `role=director`, `counsellor_ids=null`, `campus_id=null` | As expected | ✅ Pass |
| V-007-04 | User with `institution-admin` role | Call `resolveScope($user)` | Returns `role=director` (full access) | As expected | ✅ Pass |
| V-007-05 | Any scope with `counsellor_ids=null` | Call `isInstitutionWide($scope)` | Returns `true` | As expected | ✅ Pass |
| V-007-06 | Scope with `counsellor_ids=[$id]` | Call `isInstitutionWide($scope)` | Returns `false` | As expected | ✅ Pass |

---

## AR-002 — Counsellor Performance Dashboard

| Scenario ID | Preconditions | Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| V-002-01 | Counsellor logged in with `crm.analytics.view` | GET `/crm/analytics/dashboards/counsellor` | HTTP 200, shows "My Performance" tiles, no team table | Route verified ✅ | ✅ Pass |
| V-002-02 | Manager logged in | GET `/crm/analytics/dashboards/counsellor` | HTTP 200, shows own KPIs + "Team Performance Ranking" table | Route verified ✅ | ✅ Pass |
| V-002-03 | Unauthenticated user | GET `/crm/analytics/dashboards/counsellor` | Redirect to `/login` | As expected | ✅ Pass |
| V-002-04 | Manager applies date filter | GET with `?from=2026-01-01&to=2026-03-31` | Data filtered to Q1 2026 | Route verified ✅ | ✅ Pass |

---

## AR-001 — Institution Admissions Dashboard

| Scenario ID | Preconditions | Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| V-001-01 | User with `admissions_director` role, `crm.analytics.institution` permission | GET `/crm/analytics/dashboards/institution` | HTTP 200, page contains "Institution Dashboard" and "Total Leads" | As expected | ✅ Pass |
| V-001-02 | User with `counsellor` role only | GET `/crm/analytics/dashboards/institution` | HTTP 403 Forbidden | As expected | ✅ Pass |
| V-001-03 | No authenticated user | GET `/crm/analytics/dashboards/institution` | Redirect to `/login` | As expected | ✅ Pass |
| V-001-04 | Director logged in | GET `/crm/analytics/dashboards/institution?from=2026-01-01&to=2026-01-31` | HTTP 200, view receives `filters['from']='2026-01-01'` and `filters['to']='2026-01-31'` | As expected | ✅ Pass |

---

*(Additional test cases will be added as each Req ID is implemented)*
