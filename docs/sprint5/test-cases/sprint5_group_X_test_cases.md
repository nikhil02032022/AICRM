# Sprint 5 Group X — Test Cases

**BRD Req IDs:** CRM-AI-001
**Generated:** 2026-04-24
**Total Test Cases:** 20

---

## Unit Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-X-U-001 | AI-001 | LeadSignalAggregatorService returns array with all expected signal keys | Array contains source_quality, response_time_hours, page_views, document_completion_pct, payment_attempts, session_count, communication_frequency | LeadSignalAggregatorServiceTest |
| TC-X-U-002 | AI-001 | LeadSignalAggregatorService scopes signals to lead's institution (no cross-tenant data) | Signal counts match only this lead's records | LeadSignalAggregatorServiceTest |
| TC-X-U-003 | AI-001 | LeadSignalAggregatorService caps signal window to last 90 days | Events older than 90 days excluded from counts | LeadSignalAggregatorServiceTest |
| TC-X-U-004 | AI-001 | ConversionPredictionService buildPrompt returns string with no PII (email/phone/name patterns) | Prompt string passes regex check for absence of email and phone patterns | ConversionPredictionServiceTest |
| TC-X-U-005 | AI-001 | ConversionPredictionService parseResponse extracts conversion_probability between 0 and 1 | Parsed probability is float in range [0.0, 1.0] | ConversionPredictionServiceTest |
| TC-X-U-006 | AI-001 | ConversionPredictionService parseResponse extracts top 3 prediction_factors as array | prediction_factors array has exactly 3 elements | ConversionPredictionServiceTest |
| TC-X-U-007 | AI-001 | ConversionPredictionService returns null gracefully on Claude API timeout | Returns null; does not throw; transcription_status set to failed | ConversionPredictionServiceTest |
| TC-X-U-008 | AI-001 | ConfidenceLevel enum returns High for probability >0.75 | ConfidenceLevel::fromProbability(0.80) === High | ConversionPredictionServiceTest |
| TC-X-U-009 | AI-001 | ConfidenceLevel enum returns Low for probability <0.45 | ConfidenceLevel::fromProbability(0.30) === Low | ConversionPredictionServiceTest |

---

## Feature Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-X-F-001 | AI-001 | RefreshConversionPredictionJob updates ai_lead_scores with probability and status=Completed | conversion_probability not null, prediction_status=completed | RefreshConversionPredictionJobTest |
| TC-X-F-002 | AI-001 | RefreshConversionPredictionJob acquires Redis lock per lead and skips duplicate execution | Second job dispatch returns without processing; ai_usage_logs has exactly 1 entry | RefreshConversionPredictionJobTest |
| TC-X-F-003 | AI-001 | RefreshConversionPredictionJob sets prediction_status=failed on Claude API error | prediction_status=failed; no exception thrown; failed job not created | RefreshConversionPredictionJobTest |
| TC-X-F-004 | AI-001 | Lead stage change fires RefreshConversionPredictionJob via observer | Job dispatched to crm-ai queue after LeadStageChanged event | RefreshConversionPredictionJobTest |
| TC-X-F-005 | AI-001 | Lead detail page renders ConversionProbabilityBadge with correct probability value | HTTP 200; response contains probability percentage and confidence label | PredictionBadgeDisplayTest |
| TC-X-F-006 | AI-001 | Lead detail page renders badge with Insufficient Data state when confidence < 0.3 | Response contains "Insufficient data" text; no percentage shown | PredictionBadgeDisplayTest |
| TC-X-F-007 | AI-001 | Lead index page renders probability column for all listed leads | HTTP 200; table contains conversion_probability column | PredictionBadgeDisplayTest |
| TC-X-F-008 | AI-001 | POST accept prediction saves AiSuggestionDecision with decision=accepted and counsellor ID | 200; decision record created with correct counsellor_id and accepted_at timestamp | PredictionFeedbackTest |
| TC-X-F-009 | AI-001 | POST reject prediction saves AiSuggestionDecision with decision=rejected | 200; decision record created with rejected_at timestamp | PredictionFeedbackTest |
| TC-X-F-010 | AI-001 | Counsellor cannot accept prediction for cross-tenant lead | 404 Not Found | PredictionFeedbackTest |
| TC-X-F-011 | AI-001 | AiUsageLoggingService records Claude API call with model version and token count | ai_usage_logs entry created with model=claude-sonnet-4-6, token_count > 0 | RefreshConversionPredictionJobTest |

---

## Coverage Notes

- CRM-AI-001 covered by all 20 test cases
- Multi-tenancy isolation verified in: TC-X-U-002, TC-X-F-010
- PII non-disclosure in Claude prompt verified in: TC-X-U-004
- Graceful API failure handling verified in: TC-X-U-007, TC-X-F-003
- Redis lock idempotency verified in: TC-X-F-002
- DPDP compliance: prediction factors contain aggregate metrics only — verified in TC-X-U-004 (no PII in prompt) and TC-X-U-001 (signals are counts/durations, not names or contact details)
