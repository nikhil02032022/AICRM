# Sprint 5 Group AB — Test Cases

**BRD Req IDs:** CRM-AR-021
**Generated:** 2026-04-24
**Total Test Cases:** 14

---

## Unit Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-AB-U-001 | AR-021 | AnalyticsApiService::getLeadFunnelMetrics returns array with stage_counts and conversion_rate keys | Array contains stage_counts (array) and conversion_rate (float) | AnalyticsApiServiceTest |
| TC-AB-U-002 | AR-021 | AnalyticsApiService::getLeadFunnelMetrics filters results to specified institution only | All returned stage counts match only leads of institution_id=X | AnalyticsApiServiceTest |
| TC-AB-U-003 | AR-021 | AnalyticsApiService::getLeadFunnelMetrics applies from_date and to_date filters correctly | Leads outside date range excluded from counts | AnalyticsApiServiceTest |
| TC-AB-U-004 | AR-021 | AnalyticsApiService response contains no PII fields (no email, name, phone in any key) | Array keys and values pass PII regex check | AnalyticsApiServiceTest |

---

## Feature Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-AB-F-001 | AR-021 | GET /api/crm/v1/analytics/leads with valid Bearer token returns 200 with data and meta | 200; response JSON has data and meta keys; meta.institution_id matches token | AnalyticsApiLeadsTest |
| TC-AB-F-002 | AR-021 | GET /api/crm/v1/analytics/leads with no Bearer token returns 401 | 401 Unauthorized | AnalyticsApiAuthTest |
| TC-AB-F-003 | AR-021 | GET /api/crm/v1/analytics/leads with expired token returns 401 | 401 Unauthorized | AnalyticsApiAuthTest |
| TC-AB-F-004 | AR-021 | GET /api/crm/v1/analytics/leads with token from Institution A does not return Institution B leads | All stage_counts in response match only Institution A data | AnalyticsApiLeadsTest |
| TC-AB-F-005 | AR-021 | GET /api/crm/v1/analytics/pipeline with valid token returns application stage counts | 200; response contains stage_counts for application statuses | AnalyticsApiPipelineTest |
| TC-AB-F-006 | AR-021 | GET /api/crm/v1/analytics/leads with invalid from_date format returns 422 | 422 Unprocessable with from_date validation error | AnalyticsApiLeadsTest |
| TC-AB-F-007 | AR-021 | Rate limit: 61st request within 1 minute returns 429 | 429 Too Many Requests | AnalyticsApiAuthTest |
| TC-AB-F-008 | AR-021 | Admin can create API token from System Config UI and token is stored hashed | HTTP 200/redirect; personal_access_tokens record created; token value shown once in response | ApiTokenManagementTest |
| TC-AB-F-009 | AR-021 | Admin can revoke API token from System Config UI | Token record deleted; subsequent API call with revoked token returns 401 | ApiTokenManagementTest |
| TC-AB-F-010 | AR-021 | Non-admin user cannot access API token management UI | 403 Forbidden | ApiTokenManagementTest |

---

## Coverage Notes

- CRM-AR-021 covered by all 14 test cases
- Multi-tenancy isolation verified in: TC-AB-U-002, TC-AB-F-004
- Authentication and authorisation verified in: TC-AB-F-002, TC-AB-F-003, TC-AB-F-007, TC-AB-F-010
- PII non-disclosure verified in: TC-AB-U-004
- Rate limiting verified in: TC-AB-F-007
- DPDP compliance: Analytics API returns only aggregate metrics — no individual applicant PII returned by any endpoint. Verified in TC-AB-U-004. Token management restricted to admin role (TC-AB-F-010).
- Date filter validation verified in: TC-AB-F-006
