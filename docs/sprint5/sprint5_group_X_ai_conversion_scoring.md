# Sprint 5 - Group X: AI-Assisted Lead Scoring

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** X
**Module:** AI and Agentic Features
**Req IDs:** CRM-AI-001
**Status:** Completed
**Completed:** 2026-04-24
**Dependencies:** AiLeadScore model (Sprint 2 Group I), CommunicationLog (Sprint 1 Group F), CounsellingSession (Sprint 1 Group E), AiSuggestionDecision (Sprint 2 Group I), AiUsageLoggingService (Sprint 2 Group I), Claude API credentials

---

## Objective

Deliver Claude API-powered conversion probability prediction for every lead, analysing behavioural signals and historical interaction patterns to generate a 0–1 probability score with confidence level and top prediction factors — replacing the current rule-based scoring with an AI-enriched model that learns from counsellor feedback.

## In Scope

1. Aggregation of per-lead behavioural signals: lead source quality, time-to-first-contact, page views, document completion percentage, payment attempt count, counselling session count, inbound communication frequency.
2. Claude API (claude-sonnet-4-6) structured prompt construction with lead context window.
3. Parsing API response to extract conversion_probability (0.0–1.0), confidence_score, and top 3 prediction_factors.
4. Persisting prediction results in extended ai_lead_scores table.
5. Asynchronous prediction refresh via RefreshConversionPredictionJob on crm-ai queue.
6. Event-driven triggers: lead stage change, inbound communication received, score override saved, counselling session created.
7. Conversion probability badge on lead index view and lead detail view.
8. Counsellor accept/reject of AI prediction logged via existing AiSuggestionDecision model.
9. All Claude API calls logged through AiUsageLoggingService; PII scrubbed via PayloadRedactor.

## Out of Scope

- Audio or call content analysis (AI-007 — Group AA).
- Model fine-tuning or self-hosted ML models (Phase 2).
- Churn prediction (AI-003 — completed Sprint 2 Group I).
- Next-best-action journeys (AI-010 — completed Sprint 2 Group I).

## Dependencies

1. `AiLeadScore` model from Sprint 2 Group I — extended with new columns via migration.
2. `CommunicationLog` model from Sprint 1 Group F — for communication frequency signal.
3. `CounsellingSession` model from Sprint 1 Group E — for session count signal.
4. `LeadAttribution` model from Sprint 2 Group H — for source quality signal.
5. `AiSuggestionDecision` model from Sprint 2 Group I — for feedback loop logging.
6. `AiUsageLoggingService` from Sprint 2 Group I — for Claude API call audit.
7. `PayloadRedactor` utility — for PII scrubbing before logging.
8. `crm-ai` Laravel Horizon queue — pre-existing from Sprint 2.
9. `ANTHROPIC_API_KEY` environment variable — must be set in production .env.

## Design Notes

1. Use web controllers and Blade views for lead views; AI prediction displayed as a badge component.
2. ConversionPredictionService must handle Claude API timeouts gracefully — log error, set transcription_status to `failed`, do not block lead view.
3. Prompt engineering: include only non-PII signals in the prompt (counts, durations, flags — not names or contact details); PII scrubbing enforced by PayloadRedactor.
4. Prediction is advisory only — counsellor can mark prediction as accepted or rejected via existing AiSuggestionDecision; rejection triggers a re-prompt with counsellor override note appended.
5. Confidence score thresholds: >0.75 = High Confidence badge, 0.45–0.75 = Moderate, <0.45 = Low.
6. Job uses atomic Redis lock per lead ID to prevent concurrent duplicate predictions.
7. All new Eloquent models must use InstitutionScope trait.

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for AI Conversion Scoring feature (counsellor and manager usage).
3. Group X test cases document (`test-cases/sprint5_group_X_test_cases.md`).
4. Master tracker status and remarks update.

## Acceptance Gates

1. Lead detail view shows conversion probability badge with percentage and confidence level.
2. Probability refreshes automatically (async) when counsellor changes lead stage.
3. Claude API call is never blocking — page loads even if API is unavailable.
4. PII (name, email, phone) is not present in any Claude API request payload.
5. Counsellor can accept or reject an AI prediction; decision is recorded with timestamp and counsellor ID.
6. All API calls appear in ai_usage_logs with token count and model version.
7. No cross-tenant lead data included in any lead's prediction context.

## Risks and Mitigation

1. Claude API latency (>5 seconds) impacting counsellor workflow:
   Mitigation: Always run prediction in background job; display stale prediction with `last updated` timestamp rather than blocking UI.
2. Prompt context window exceeding model token limit for leads with long histories:
   Mitigation: Cap signal window at last 90 days; summarise older history into a single line count.
3. Low prediction accuracy in early deployments with sparse data:
   Mitigation: Display confidence score; suppress probability badge if confidence < 0.3 with "Insufficient data" state.

## Exit Criteria

1. AI-001 marked completed in master tracker.
2. ~20 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. QA sign-off recorded.

---

## File Manifest

### Migrations
- `database/migrations/2026_05_01_000001_add_conversion_prediction_to_ai_lead_scores.php` ✅ — adds conversion_probability (decimal 5,4), confidence_score (decimal 5,4), prediction_factors (JSON), prediction_refreshed_at (timestamp nullable), prediction_status (enum: pending/processing/completed/failed) to ai_lead_scores

### Enums
- `app/Enums/CRM/AI/PredictionStatus.php` ✅ — Pending, Processing, Completed, Failed
- `app/Enums/CRM/AI/ConfidenceLevel.php` ✅ — High (>0.75), Moderate (0.45–0.75), Low (<0.45)

### Models (updated)
- `app/Models/CRM/AiLeadScore.php` ✅ — 5 new fillable fields, casts, `conversionConfidenceLevel()`, `conversionPercentage()`
- `app/Models/CRM/Lead.php` ✅ — added `latestPrediction(): HasOne`
- `app/Models/CRM/CommunicationLog.php` ✅ — fires `CommunicationLogCreatedEvent` on create

### Factories (new)
- `database/factories/CRM/CommunicationLogFactory.php` ✅ — created for test support

### Events (new)
- `app/Events/CRM/CommunicationLogCreatedEvent.php` ✅ — was missing; now fires from CommunicationLog::booted()

### Services (new)
- `app/Services/CRM/AI/ConversionPredictionService.php` ✅ — Claude API via Http facade, signal aggregation, PII scrub, usage logging
- `app/Services/CRM/AI/LeadSignalAggregatorService.php` ✅ — 13 PII-free signals, 90-day window

### Jobs (new)
- `app/Jobs/CRM/AI/RefreshConversionPredictionJob.php` ✅ — ShouldBeUnique, Redis lock per institution+lead, `ai` queue

### Observers (new)
- `app/Observers/CRM/AI/LeadPredictionObserver.php` ✅ — event subscriber; 4 domain events; inbound-only communications filter

### Controllers (new)
- `app/Http/Controllers/Web/CRM/AiPredictionWebController.php` ✅ — `prediction()` JSON, `refresh()` dispatch
- `app/Http/Controllers/Web/CRM/AiPredictionFeedbackController.php` ✅ — `store()` accept/reject

### Livewire Components (new)
- `app/Livewire/CRM/Lead/ConversionProbabilityBadge.php` ✅ — computed score, poll guard, 5 badge states

### Views (new / updated)
- `resources/views/livewire/crm/leads/conversion-probability-badge.blade.php` ✅ — SVG ring, factors, accept/reject forms
- `resources/views/crm/leads/_partials/sidebar.blade.php` ✅ — badge mounted with `@can('ai.prediction.view', $lead)` guard
- `resources/views/livewire/crm/lead/lead-table.blade.php` ✅ — Conv. % column added

### Livewire (updated)
- `app/Livewire/CRM/Lead/LeadTable.php` ✅ — `latestPrediction` eager-loaded

### Policies (new)
- `app/Policies/CRM/AI/AiPredictionPolicy.php` ✅ — `viewPrediction()`, `feedback()`

### Seeders (new)
- `database/seeders/CRM/AI/AiPredictionPermissionSeeder.php` ✅ — `ai.prediction.view`, `ai.prediction.feedback` assigned to 6 roles

### Providers (updated)
- `app/Providers/CRM/CrmAiServiceProvider.php` ✅ — singletons, Gate definitions, Event subscriber

### Routes (updated)
- `routes/web.php` ✅ — 3 routes added: `crm.leads.ai-prediction`, `crm.leads.ai-prediction.refresh`, `crm.leads.ai-prediction.feedback`

### Tests (new)
- `tests/Unit/CRM/AI/ConversionPredictionServiceTest.php` ✅ — 8 tests passing
- `tests/Unit/CRM/AI/LeadSignalAggregatorServiceTest.php` ✅ — 4 tests passing
- `tests/Feature/CRM/AI/RefreshConversionPredictionJobTest.php` ✅ — 3 tests passing
- `tests/Feature/CRM/AI/PredictionFeedbackTest.php` ✅ — 3 tests passing
- `tests/Feature/CRM/AI/PredictionBadgeDisplayTest.php` ✅ — 2 tests passing

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| AI-001 | AI-assisted lead scoring shall analyse lead behaviour patterns and historical conversion data to predict conversion probability | `ConversionPredictionService`, `LeadSignalAggregatorService`, `RefreshConversionPredictionJob`, extended `AiLeadScore` model, `ConversionProbabilityBadge` Livewire component |

---

## Security Checklist

- [x] All prediction routes protected by `auth` and `permission` middleware.
- [x] `AiPredictionPolicy::viewPrediction()` restricts to counsellors and above within same institution.
- [x] Claude API payload verified to contain zero PII fields (name, email, phone, address) — enforced by `scrubPii()` in ConversionPredictionService and confirmed by unit test.
- [x] Redis lock key includes institution_id and lead_id to prevent cross-tenant lock collisions.
- [x] API key stored in encrypted environment variable; never logged or exposed in API responses.
- [x] DPDP: prediction factors stored as aggregated metrics only, not raw personal data.

---

## Implementation Log

**Status:** Completed — 2026-04-24
**Tests:** 20/20 passing (8 unit + 12 feature)
**Migration:** Applied — 5 new columns on `ai_lead_scores`
**Permissions seeded:** `ai.prediction.view`, `ai.prediction.feedback`

### Actual Implementation — Deviations from Spec

| Spec | Actual | Reason |
|------|--------|--------|
| `anthropic/sdk` package | Laravel `Http::` facade directly | No Anthropic PHP SDK in composer.json; HTTP client pattern matches existing ChatbotService |
| `crm-ai` queue | `ai` queue | Existing `supervisor-ai` in horizon.php handles the `ai` queue (3 workers, 120 s timeout) |
| `App\Http\Controllers\CRM\Web\AiController` updated | New `AiPredictionWebController` created | No existing AiController in this namespace |
| `App\Livewire\CRM\Leads\` (plural) | `App\Livewire\CRM\Lead\` (singular) | Matches existing Livewire convention in project |
| `ScoreOverrideSavedEvent` (new) | `ScoreChangedEvent` (existing) | ScoreChangedEvent already exists and is dispatched from `LeadScoringService::applyManualOverride()` |
| PayloadRedactor for PII scrub | Inline `scrubPii()` in ConversionPredictionService | PayloadRedactor is payment-focused; inline filter is simpler and scoped |
| Cross-tenant feedback → 403 | 404 | InstitutionScope hides cross-tenant leads at route model binding; 404 is correct multi-tenant resource-hiding behavior |

### Completed Phases

**Phase A — Migration** ✅
- `database/migrations/2026_05_01_000001_add_conversion_prediction_to_ai_lead_scores.php`
- Added: `conversion_probability` (decimal 5,4), `confidence_score` (decimal 5,4), `prediction_factors` (json), `prediction_refreshed_at` (timestamp), `prediction_status` (enum)

**Phase B — Enums** ✅
- `app/Enums/CRM/AI/PredictionStatus.php` — Pending, Processing, Completed, Failed; `label()`, `isTerminal()`
- `app/Enums/CRM/AI/ConfidenceLevel.php` — High (>0.75), Moderate (0.45–0.75), Low (<0.45); `fromScore()`, `badgeClass()`, `ringClass()`

**Phase C — Model Updates** ✅
- `app/Models/CRM/AiLeadScore.php` — 5 new fillable fields, casts, `conversionConfidenceLevel()`, `conversionPercentage()`
- `app/Models/CRM/Lead.php` — `latestPrediction(): HasOne` via `latestOfMany('calculated_at')`
- `app/Models/CRM/CommunicationLog.php` — `CommunicationLogCreatedEvent` fired from `booted()::created()`

**Phase D — Services** ✅
- `app/Services/CRM/AI/LeadSignalAggregatorService.php` — 13 PII-free signals, 90-day window, source quality map
- `app/Services/CRM/AI/ConversionPredictionService.php` — Claude API via Http facade, graceful timeout, confidence suppression, PII scrub, usage logging

**Phase E — Job** ✅
- `app/Jobs/CRM/AI/RefreshConversionPredictionJob.php` — ShouldBeUnique, Redis lock, `ai` queue, 2 tries, 120 s timeout

**Phase F — Events and Observer** ✅
- `app/Events/CRM/CommunicationLogCreatedEvent.php` — created (was missing)
- `app/Observers/CRM/AI/LeadPredictionObserver.php` — event subscriber, 4 domain events, inbound-only filter for communications

**Phase G — HTTP Layer** ✅
- `app/Http/Controllers/Web/CRM/AiPredictionWebController.php` — `prediction()` JSON endpoint, `refresh()` dispatch
- `app/Http/Controllers/Web/CRM/AiPredictionFeedbackController.php` — `store()` accept/reject
- 3 named routes added to `routes/web.php` under `crm.` group

**Phase H — Livewire and Views** ✅
- `app/Livewire/CRM/Lead/ConversionProbabilityBadge.php` — computed score, poll guard, 5 badge states
- `resources/views/livewire/crm/leads/conversion-probability-badge.blade.php` — SVG ring, factors list, accept/reject forms
- `resources/views/crm/leads/_partials/sidebar.blade.php` — badge mounted with `@can` guard
- `app/Livewire/CRM/Lead/LeadTable.php` — `latestPrediction` eager-loaded
- `resources/views/livewire/crm/lead/lead-table.blade.php` — Conv. % column added

**Phase I — Policy, Seeder, Provider** ✅
- `app/Policies/CRM/AI/AiPredictionPolicy.php` — `viewPrediction()`, `feedback()`
- `database/seeders/CRM/AI/AiPredictionPermissionSeeder.php` — 2 permissions, 6 roles
- `app/Providers/CRM/CrmAiServiceProvider.php` — singletons, Gate definitions, Event subscriber

**Phase J — Tests** ✅
- 20 Pest tests written and passing
- `database/factories/CRM/CommunicationLogFactory.php` — created (was missing)
