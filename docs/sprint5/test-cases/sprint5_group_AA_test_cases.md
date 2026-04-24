# Sprint 5 Group AA — Test Cases

**BRD Req IDs:** CRM-AI-007
**Generated:** 2026-04-24
**Total Test Cases:** 15

---

## Unit Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-AA-U-001 | AI-007 | CallTranscriptionService::buildPrompt returns string containing all required extraction keys | Prompt contains "interests", "objections", "next_steps", "lead_temperature", "summary_sentence" | CallTranscriptionServiceTest |
| TC-AA-U-002 | AI-007 | CallTranscriptionService::buildPrompt strips PII via PayloadRedactor before logging (email pattern replaced) | Logged prompt does not match email regex; raw transcript is unmodified | CallTranscriptionServiceTest |
| TC-AA-U-003 | AI-007 | CallTranscriptionService::parseResponse correctly parses valid Claude JSON output | Returns array with all 5 expected keys; lead_temperature is one of Hot/Warm/Cold | CallTranscriptionServiceTest |
| TC-AA-U-004 | AI-007 | CallTranscriptionService::parseResponse returns null on malformed JSON | Returns null; no exception thrown | CallTranscriptionServiceTest |
| TC-AA-U-005 | AI-007 | CallTranscriptionService::validateStructure returns false when required key missing | False returned when interests key absent from response | CallTranscriptionServiceTest |
| TC-AA-U-006 | AI-007 | CallTranscriptionService truncates transcript exceeding 8000 tokens with warning flag | Transcript trimmed; truncation_warning=true in returned metadata | CallTranscriptionServiceTest |

---

## Feature Tests

| TC ID | BRD Req | Test Description | Expected Result | File |
|-------|---------|-----------------|-----------------|------|
| TC-AA-F-001 | AI-007 | Call completion form renders Transcript textarea field | HTTP 200 on GET complete form; response contains transcript textarea | CallCompletionTranscriptTest |
| TC-AA-F-002 | AI-007 | POST call completion with transcript text dispatches TranscribeCallJob | Job dispatched to crm-ai queue; transcription_status=pending on call_log | CallCompletionTranscriptTest |
| TC-AA-F-003 | AI-007 | POST call completion without transcript does NOT dispatch TranscribeCallJob | No job dispatched; transcription_status null on call_log | CallCompletionTranscriptTest |
| TC-AA-F-004 | AI-007 | TranscribeCallJob sets transcription_status=Completed and stores structured summary on success | call_log.transcription_status=completed; transcription_summary JSON not null | TranscribeCallJobTest |
| TC-AA-F-005 | AI-007 | TranscribeCallJob sets transcription_status=Failed on Claude API error | transcription_status=failed; no job exception propagated | TranscribeCallJobTest |
| TC-AA-F-006 | AI-007 | TranscribeCallJob skips processing if transcription_status already Completed (idempotency) | Claude API not called; transcription_summary unchanged | TranscribeCallJobTest |
| TC-AA-F-007 | AI-007 | TranscribeCallJob auto-populates disposition notes when notes field was blank | call_log.notes updated with summary_sentence from AI response | TranscribeCallJobTest |
| TC-AA-F-008 | AI-007 | Call log detail view shows AI Summary panel when transcription_status=Completed | HTTP 200; response contains interests, objections, next_steps sections | TranscribeCallJobTest |
| TC-AA-F-009 | AI-007 | POST retry transcription re-dispatches TranscribeCallJob for Failed status log | Job dispatched; transcription_status set back to pending | TranscribeCallJobTest |

---

## Coverage Notes

- CRM-AI-007 covered by all 15 test cases
- Multi-tenancy isolation: TranscribeCallJob scoped to call_log's institution via InstitutionScope — cross-tenant call log retry returns 404 (verified via policy)
- PII scrubbing verified in: TC-AA-U-002
- Idempotency verified in: TC-AA-F-006
- Graceful API failure handling verified in: TC-AA-U-004, TC-AA-F-005
- DPDP compliance: transcript text treated as personal data — PiiErasureService must clear transcript_text and transcription_summary when lead record is erased (documented in Group AC OWASP review; PiiErasureService update is a follow-up task)
- Queue job dispatch conditional on non-empty transcript verified in: TC-AA-F-002, TC-AA-F-003
