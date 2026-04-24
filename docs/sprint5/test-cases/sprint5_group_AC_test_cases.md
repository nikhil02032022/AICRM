# Sprint 5 Group AC — Test Cases

**BRD Req IDs:** NFR-P-001 to NFR-P-005, NFR-SE-001 to NFR-SE-007, NFR-AV-001 to NFR-AV-004, NFR-MT-001 to NFR-MT-004
**Generated:** 2026-04-24
**Total Test Cases:** 18

---

## Unit Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-AC-U-001 | NFR-SE-003 | MfaService::enableMfa returns QR code URL string and array of recovery codes | QR URL is non-empty string; recovery codes array has 8 elements | MfaServiceTest |
| TC-AC-U-002 | NFR-SE-003 | MfaService::verifyTotp returns true for valid TOTP code | Returns true | MfaServiceTest |
| TC-AC-U-003 | NFR-SE-003 | MfaService::verifyTotp returns false for invalid TOTP code | Returns false; no exception | MfaServiceTest |
| TC-AC-U-004 | NFR-AV-001 | HealthCheckService::check returns status=ok when DB, Redis, and queue are available | Array with status=ok and all component statuses=ok | HealthCheckServiceTest |
| TC-AC-U-005 | NFR-AV-001 | HealthCheckService::check returns status=degraded when queue check fails | Array with status=degraded; queue component status=fail | HealthCheckServiceTest |
| TC-AC-U-006 | NFR-SE-003 | MfaService::disableMfa clears google2fa_secret and recovery codes | User.google2fa_secret null; mfa_enabled_at null | MfaServiceTest |

---

## Feature Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-AC-F-001 | NFR-AV-001 | GET /health returns 200 with JSON status when all services healthy | 200; {"status":"ok","db":"ok","redis":"ok","queue":"ok"} | HealthEndpointTest |
| TC-AC-F-002 | NFR-AV-001 | GET /health returns 503 when DB is unavailable | 503; {"status":"degraded","db":"fail"} | HealthEndpointTest |
| TC-AC-F-003 | NFR-AV-001 | GET /health is accessible without authentication | 200 without auth header | HealthEndpointTest |
| TC-AC-F-004 | NFR-SE-003 | Admin user with MFA enabled is redirected to TOTP verify screen on login | Redirect to /crm/mfa/verify after credentials accepted | MfaSetupFlowTest |
| TC-AC-F-005 | NFR-SE-003 | Admin user submits correct TOTP on verify screen and accesses CRM | Redirect to /crm/dashboard; session mfa_verified=true | MfaSetupFlowTest |
| TC-AC-F-006 | NFR-SE-003 | Admin user submits incorrect TOTP on verify screen and is rejected | Redirect back to verify with error message; session mfa_verified remains false | MfaSetupFlowTest |
| TC-AC-F-007 | NFR-SE-005 | AdminIpWhitelist middleware passes request when IP whitelist is empty | HTTP 200 on /crm/admin route | AdminIpWhitelistTest |
| TC-AC-F-008 | NFR-SE-005 | AdminIpWhitelist middleware blocks request from unlisted IP when whitelist is configured | 403 Forbidden | AdminIpWhitelistTest |
| TC-AC-F-009 | NFR-SE-005 | AdminIpWhitelist middleware allows request from listed IP | HTTP 200 on /crm/admin route | AdminIpWhitelistTest |
| TC-AC-F-010 | NFR-SE-005 | AdminIpWhitelist middleware does not apply to /health endpoint | HTTP 200 on /health from unlisted IP even when whitelist is configured | AdminIpWhitelistTest |
| TC-AC-F-011 | NFR-P-001 | Institution dashboard loads with Redis cache hit on second request (cache read, not DB query) | Second request returns 200; Laravel Telescope or debugbar shows 0 dashboard-stat DB queries on cache hit | DashboardCacheTest |
| TC-AC-F-012 | NFR-P-001 | Dashboard cache is invalidated when a new lead is created for the institution | After lead creation, next dashboard load triggers fresh DB query (cache miss) | DashboardCacheTest |

---

## Coverage Notes

- NFR-P-001 to NFR-P-005 (Performance): covered by TC-AC-F-011, TC-AC-F-012 (cache) plus index migration (verified via EXPLAIN in manual QA, not automated test)
- NFR-SE-001 (TLS): server configuration — not testable via PHPUnit; documented in pentest-scope.md
- NFR-SE-002 (AES-256 at rest): MFA secret encrypted storage verified in TC-AC-U-001 (secret returned from enableMfa is not the raw secret)
- NFR-SE-003 (MFA): covered by TC-AC-U-001 to TC-AC-U-003 and TC-AC-F-004 to TC-AC-F-006
- NFR-SE-004 (RBAC): existing policy tests across all prior sprint groups; verified in OWASP review
- NFR-SE-005 (IP whitelist + session): covered by TC-AC-F-007 to TC-AC-F-010
- NFR-SE-006 (Pen test): scope document deliverable — not a unit/feature test
- NFR-SE-007 (OWASP Top 10): documented review deliverable — not a unit/feature test
- NFR-AV-001 to NFR-AV-004: covered by TC-AC-U-004, TC-AC-U-005, TC-AC-F-001 to TC-AC-F-003
- NFR-MT-001 to NFR-MT-004: test coverage baseline is a manual report; API docs are a documentation deliverable
- DPDP compliance: health endpoint returns no PII or sensitive system data — verified in TC-AC-F-001 (response contains only status strings)
- MFA brute-force protection: rate limiting on /crm/mfa/verify (5 attempts/minute) — verified in manual QA; documented in security checklist
