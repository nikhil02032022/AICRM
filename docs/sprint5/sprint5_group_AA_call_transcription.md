# Sprint 5 - Group AA: AI Call Transcription and Summary

**BRD:** MEETCS-BRD-CRM-001 v1.0
**Group:** AA
**Module:** AI and Agentic Features
**Req IDs:** CRM-AI-007
**Status:** Pending
**Dependencies:** CallLog model (Sprint 2 Group J), CallDispositionService (Sprint 2 Group J), AiUsageLoggingService (Sprint 2 Group I), PayloadRedactor utility, Claude API credentials, crm-ai queue

---

## Objective

Deliver AI-powered post-call summarisation using Claude API — allowing counsellors to paste a call transcript after completing a call, with Claude automatically extracting key student interests, objections, agreed next steps, and an overall lead temperature assessment. The summary is stored against the call log and used to auto-populate disposition notes if left blank.

## In Scope

1. `transcript_text` (text nullable), `transcription_summary` (JSON nullable), `transcription_status` (enum) columns added to call_logs table.
2. Transcript input field on call completion form — counsellor pastes raw transcript text (manual input for Sprint 5).
3. `TranscribeCallJob` dispatched when a call disposition is saved with a non-empty transcript field.
4. Claude API prompt: extract structured JSON with keys — interests (array), objections (array), next_steps (array), lead_temperature (enum: Hot/Warm/Cold), summary_sentence (string).
5. Auto-populate disposition notes with summary_sentence if counsellor leaves notes blank.
6. Transcription panel on call log detail view: AI Summary card (expanded by default) with key_interests, objections, next_steps chips; raw transcript (collapsible).
7. Transcription status badge: Pending, Processing, Completed, Failed — with retry button on Failed.
8. All Claude API calls logged via AiUsageLoggingService; PII scrubbed from transcript before logging via PayloadRedactor.

## Out of Scope

- Automatic audio-to-text transcription from call recording files (Sprint 6 — requires audio provider selection: Whisper, Google STT, Azure Cognitive).
- Video call transcription (not in BRD scope).
- Real-time live transcription during call (future capability).
- AI-001 conversion prediction (Group X — separate service).

## Dependencies

1. `CallLog` model from Sprint 2 Group J — extended with transcription columns.
2. `CallDispositionService` from Sprint 2 Group J — modified to fire TranscribeCallJob when transcript provided.
3. `AiUsageLoggingService` from Sprint 2 Group I — for logging Claude API call details.
4. `PayloadRedactor` utility — to remove PII from transcript text before logging.
5. `crm-ai` Horizon queue — shared with Group X; ensure worker count is adequate.
6. `ANTHROPIC_API_KEY` environment variable — must be set.

## Design Notes

1. CallTranscriptionService is a standalone service; does not extend ConversionPredictionService from Group X — both use Claude API independently through a shared Anthropic client singleton.
2. Prompt structure: "You are an admissions counselling assistant. Analyse the following call transcript and return a JSON object with these exact keys: interests (array of strings), objections (array of strings), next_steps (array of strings), lead_temperature (one of: Hot, Warm, Cold), summary_sentence (one sentence). Return only valid JSON, no markdown. Transcript: {transcript}"
3. Transcript max length: 8000 tokens; if counsellor pastes a longer transcript, truncate from the beginning with a warning label.
4. PII scrubbing before logging: PayloadRedactor replaces email patterns, phone patterns, Aadhaar-like patterns with [REDACTED] in the logged payload — the actual transcript passed to Claude is not modified.
5. Job idempotency: if TranscribeCallJob is retried, check transcription_status; skip if already Completed.
6. Redis lock per call_log_id to prevent concurrent duplicate transcription jobs.

## Deliverables

1. Group implementation log updates (this document).
2. User manual section for call transcription feature (counsellor usage guide).
3. Group AA test cases document (`test-cases/sprint5_group_AA_test_cases.md`).
4. Master tracker status and remarks update.

## Acceptance Gates

1. Call completion form shows a Transcript (optional) textarea field.
2. Submitting a call disposition with transcript text queues TranscribeCallJob.
3. Call log detail view shows transcription_status badge and, once Completed, the AI Summary panel.
4. AI Summary panel displays interests, objections, and next steps as readable chips.
5. Disposition notes field is auto-populated with summary_sentence if it was blank on save.
6. Failed transcriptions show a Retry button that re-dispatches the job.
7. Claude API payload in ai_usage_logs does not contain lead names, emails, or phone numbers.
8. Transcription_status = Completed only after successful Claude API call and JSON parse.

## Risks and Mitigation

1. Malformed JSON returned by Claude API:
   Mitigation: Wrap JSON parse in try-catch; on failure set transcription_status = Failed and log raw response; do not throw exception in job — use failed() hook for retry scheduling.
2. Transcript text containing extensive PII (full names, Aadhaar, bank details):
   Mitigation: PayloadRedactor applied to logged payload; counsellors advised in UI tooltip not to include sensitive documents in transcript paste.
3. Horizon crm-ai queue overload if many calls complete simultaneously:
   Mitigation: TranscribeCallJob uses delay(30) seconds after dispatch to allow higher-priority conversion prediction jobs to process first; increase crm-ai worker count in horizon.php (Group AC).

## Exit Criteria

1. AI-007 marked completed in master tracker.
2. ~15 Pest tests passing (unit + feature).
3. User manual and test cases document published.
4. QA sign-off recorded.

---

## File Manifest

### Migrations
- `database/migrations/2026_05_02_000005_add_transcription_to_call_logs.php` — adds transcript_text (text nullable), transcription_summary (JSON nullable), transcription_status (enum: pending/processing/completed/failed, default null), transcription_model (string nullable, stores Claude model version), transcription_token_count (unsignedInteger nullable), transcribed_at (timestamp nullable)

### Enums
- `App\Enums\CRM\AI\TranscriptionStatus` — Pending, Processing, Completed, Failed

### Models
- `App\Models\CRM\CallLog` — updated: add transcription fillable and casts (existing model)

### Services
- `App\Services\CRM\AI\CallTranscriptionService` — transcribe(CallLog): array; buildPrompt(string transcript): string; parseResponse(string json): array; validateStructure(array): bool

### Jobs
- `App\Jobs\CRM\AI\TranscribeCallJob` — accepts CallLog model; Redis lock per call_log_id; calls CallTranscriptionService; auto-populates disposition if blank; queued on crm-ai with 30-second delay

### Controllers (Web)
- `App\Http\Controllers\CRM\Web\CallTranscriptionController` — retry (POST: re-dispatch TranscribeCallJob for failed transcription)
- `App\Http\Controllers\CRM\Web\CallLogController` — updated: add transcript_text to call completion form handling (existing controller)

### Livewire Components
- `App\Livewire\CRM\Communication\TranscriptionPanel` — displays transcription status badge, AI summary chips, and raw transcript accordion; polls every 10 seconds while status is pending/processing

### Views (Blade)
- `resources/views/crm/communication/voice/show.blade.php` — updated: add TranscriptionPanel Livewire component and transcription status badge
- `resources/views/crm/communication/voice/complete.blade.php` — updated: add Transcript textarea field with char limit indicator and tooltip
- `resources/views/livewire/crm/communication/transcription-panel.blade.php`

### Notifications
- (none — transcription result displayed inline on call log view)

### Policies
- `App\Policies\CRM\Communication\CallTranscriptionPolicy` — retry (counsellor who owns the call), view (any counsellor in institution)

### Tests
- `tests/Unit/CRM/AI/CallTranscriptionServiceTest.php`
- `tests/Unit/CRM/AI/TranscriptionPromptBuilderTest.php`
- `tests/Feature/CRM/AI/TranscribeCallJobTest.php`
- `tests/Feature/CRM/AI/CallCompletionTranscriptTest.php`

---

## BRD Traceability

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| AI-007 | AI-powered call transcription and summary generation (post-call) shall be available | `CallTranscriptionService`, `TranscribeCallJob`, extended `CallLog` model, updated call completion form with transcript input, `TranscriptionPanel` Livewire component, auto-populate disposition notes |

---

## Security Checklist

- [ ] Call log detail view protected by `auth` and institution scoping — counsellors cannot view another institution's call logs.
- [ ] Retry action protected by `CallTranscriptionPolicy::retry()` — only the call log's counsellor or a manager can retry.
- [ ] Claude API payload logged with PII scrubbed via PayloadRedactor — verified by unit test asserting no email/phone/name patterns in logged payload.
- [ ] Transcription summary JSON validated for expected keys before storage — reject and mark Failed if structure invalid.
- [ ] DPDP: transcription text treated as personal data — subject to lead record erasure policy; if lead is erased, transcript_text and transcription_summary must be cleared (extend PiiErasureService from Sprint 4 Group W).
- [ ] Redis lock prevents concurrent duplicate transcription runs for same call_log_id.

---

## Implementation Log

**Status:** Pending — implementation not yet started.

### Planned Phases

**Phase A — Migration**
- Add transcription columns to call_logs

**Phase B — Enum**
- TranscriptionStatus

**Phase C — Service**
- CallTranscriptionService with prompt builder and response parser

**Phase D — Job**
- TranscribeCallJob with Redis lock and failure handling

**Phase E — HTTP Layer**
- CallTranscriptionController (retry), updated CallLogController, routes

**Phase F — Livewire and Views**
- TranscriptionPanel Livewire, updated voice show and complete views

**Phase G — Tests**
- Unit and Feature test files

**Estimated test count:** 15 test cases
