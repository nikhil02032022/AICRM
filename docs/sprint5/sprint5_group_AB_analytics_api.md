# Sprint 5 - Group AB: Analytics API for BI Tools

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** AB
**Module:** Analytics and Reporting
**Req IDs:** CRM-AR-021
**Status:** Pending
**Dependencies:** Analytics services (Sprint 4 Group V), DashboardController (Sprint 4 Group V), Laravel Sanctum (existing), SystemConfig admin UI (Sprint 4 Group W), InstitutionScope trait

---

## Objective

Expose CRM analytics data through authenticated REST API endpoints that can be consumed by Power BI, Tableau, or any BI tool, allowing institutions to build custom reporting and visualisations on top of existing CRM data without requiring database access.

## In Scope

1. Four analytics API endpoints returning institution-scoped JSON data:
   - Lead funnel metrics (by period, source, programme — with stage-wise counts and conversion rates)
   - Application pipeline stage counts (by period and programme)
   - Fee collection summary (by period, programme, payment gateway)
   - Counsellor performance metrics (leads handled, conversions, response time, task completion rate)
2. Laravel Sanctum API token authentication — institution-scoped tokens.
3. Token management UI: issue, name, list, and revoke API tokens from Admin → System Config.
4. All endpoints: paginated, date-range filterable (from_date, to_date query params), JSON response with meta section.
5. OpenAPI 3.0 specification document at `docs/api/analytics-api.yaml`.
6. Rate limiting: 60 requests per minute per token.

## Out of Scope

- Webhook/push model (pull-only API for BI tools).
- Real-time streaming or WebSocket analytics feed.
- Granular lead-level data export via API (DPDP concern — aggregate only).
- Power BI connector plugin or Tableau extension (integration client responsibility).
- GraphQL endpoint.

## Dependencies

1. Analytics services from Sprint 4 Group V — reused for data aggregation (DashboardDataService, FunnelAnalyticsService, etc.).
2. Laravel Sanctum — must be installed and configured (likely already present; verify in composer.json).
3. `SystemConfig` admin UI from Sprint 4 Group W — token management card added here.
4. `InstitutionScope` — API tokens must carry institution_id claim to enforce scoping.
5. `CustomReport` service from Sprint 4 Group V — not directly used but confirms existing analytics service layer.

## Design Notes

1. Sanctum token ability scopes: `analytics:read` — the only scope granted to BI API tokens.
2. Institution binding: when a token is created, institution_id is stored in `personal_access_tokens.name` as a JSON prefix (e.g., `"inst:5|PowerBI Production"`) — validated in middleware to enforce scoping.
3. Alternatively (cleaner): add `institution_id` column to `personal_access_tokens` via migration; middleware reads it for scoping.
4. All endpoints return the same envelope: `{ "data": [...], "meta": { "from_date": "...", "to_date": "...", "institution_id": N, "generated_at": "..." }, "links": { "self": "..." } }`.
5. Date range defaults: if not provided, defaults to current academic year start to today.
6. Aggregation only — no individual lead PII (name, email, phone) returned by any endpoint (DPDP compliance).
7. OpenAPI spec generated manually (not auto-generated) to ensure accuracy; stored as docs/api/analytics-api.yaml.

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for Analytics API setup and Power BI / Tableau integration guide.
3. Group AB test cases document (`test-cases/sprint5_group_AB_test_cases.md`).
4. Master tracker status and remarks update.
5. OpenAPI 3.0 specification file (`docs/api/analytics-api.yaml`).

## Acceptance Gates

1. Admin can issue a named API token from System Config with `analytics:read` ability.
2. GET /api/crm/v1/analytics/leads with valid Bearer token returns lead funnel JSON with correct stage counts.
3. GET /api/crm/v1/analytics/leads with an expired or invalid token returns 401.
4. GET /api/crm/v1/analytics/leads with a token from Institution A does not return Institution B data.
5. All 4 endpoints respect from_date and to_date query parameters; invalid date format returns 422.
6. Rate limit of 60 requests/minute enforced — 61st request returns 429.
7. No lead-level PII (name, email, phone) present in any API response.
8. OpenAPI spec file exists at docs/api/analytics-api.yaml and matches actual endpoint contracts.

## Risks and Mitigation

1. Sanctum not installed — blocking setup:
   Mitigation: Verify via composer.json before implementation; if missing, run `composer require laravel/sanctum` as first step.
2. Analytics service queries too slow for API consumers (>2 seconds):
   Mitigation: Reuse existing Redis cache layer from Group AC NFR; add cache key per token institution_id + date range.
3. Token leakage — institution admin accidentally shares token:
   Mitigation: Token shown only once at creation (standard Sanctum behaviour); provide revoke button; document rotation best practice in user manual.

## Exit Criteria

1. AR-021 marked completed in master tracker.
2. ~14 Pest tests passing (unit + feature).
3. User manual, test cases document, and OpenAPI spec published.
4. QA sign-off recorded.

---

## File Manifest

### Migrations
- `database/migrations/2026_05_03_000001_add_institution_id_to_personal_access_tokens.php` — adds institution_id (unsignedBigInteger nullable) to personal_access_tokens table for institution-scoped token enforcement

### Enums
- (none — Sanctum uses string ability scopes)

### Models
- (none new — uses Sanctum's PersonalAccessToken; existing User model updated with HasApiTokens if not already present)

### Services
- `App\Services\CRM\Analytics\AnalyticsApiService` — getLeadFunnelMetrics(Institution, Carbon from, Carbon to): array; getPipelineMetrics(Institution, Carbon from, Carbon to): array; getFeeCollectionMetrics(Institution, Carbon from, Carbon to): array; getCounsellorPerformanceMetrics(Institution, Carbon from, Carbon to): array
  (delegates to existing DashboardDataService and FunnelAnalyticsService from Sprint 4)

### Jobs
- (none — synchronous API responses)

### Controllers (Web)
- `App\Http\Controllers\CRM\Admin\ApiTokenController` — index, store, destroy (token management UI in System Config)

### Controllers (API)
- `App\Http\Controllers\CRM\Api\AnalyticsApiController` — leads, pipeline, feeCollection, counsellorPerformance
  (in existing `app/Http/Controllers/CRM/Api/` directory)

### Views (Blade)
- `resources/views/crm/admin/system-config/index.blade.php` — updated: add API Token Management card section (existing view, updated)
- `resources/views/crm/admin/api-tokens/index.blade.php` — list of active tokens with revoke button and issue form

### Notifications
- (none)

### Policies
- `App\Policies\CRM\Admin\ApiTokenPolicy` — manage (admin only)

### Seeders
- `Database\Seeders\CRM\Admin\ApiTokenPermissionSeeder` — api_token.manage permission for admin role

### Documentation
- `docs/api/analytics-api.yaml` — OpenAPI 3.0 specification with all 4 endpoints, auth scheme, request/response schemas, error codes

### Tests
- `tests/Unit/CRM/Analytics/AnalyticsApiServiceTest.php`
- `tests/Feature/CRM/Analytics/AnalyticsApiLeadsTest.php`
- `tests/Feature/CRM/Analytics/AnalyticsApiPipelineTest.php`
- `tests/Feature/CRM/Analytics/AnalyticsApiAuthTest.php`
- `tests/Feature/CRM/Analytics/ApiTokenManagementTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| AR-021 | API access to analytics data for Power BI / Tableau integration shall be available | `AnalyticsApiController` (4 endpoints), `AnalyticsApiService`, Sanctum token auth with `analytics:read` scope, institution-scoped token management UI, rate limiting, OpenAPI 3.0 spec |

---

## Security Checklist

- [ ] All API routes under `/api/crm/v1/analytics/*` protected by `auth:sanctum` middleware.
- [ ] `analytics:read` token ability checked on every request — tokens without this scope return 403.
- [ ] institution_id on token enforced in AnalyticsApiController before delegating to service.
- [ ] No PII in any API response — verified by feature tests asserting absence of email/name/phone fields.
- [ ] Rate limiting applied via `throttle:60,1` middleware on analytics API route group.
- [ ] ApiTokenPolicy restricts token management to admin role only — counsellors cannot issue API tokens.
- [ ] Token shown only once at creation (Sanctum standard); plaintext never stored in DB.
- [ ] DPDP: analytics data returned is aggregate only; individual applicant records never returned via this API.

---

## Implementation Log

**Status:** Pending — implementation not yet started.

### Planned Phases

**Phase A — Migration and Sanctum setup**
- Add institution_id to personal_access_tokens

**Phase B — Service**
- AnalyticsApiService delegating to existing Sprint 4 analytics services

**Phase C — HTTP Layer**
- AnalyticsApiController, ApiTokenController, routes, middleware

**Phase D — Views**
- Token management UI card in system-config admin view

**Phase E — Documentation**
- OpenAPI 3.0 spec file

**Phase F — Tests**
- Unit and Feature test files

**Estimated test count:** 14 test cases
