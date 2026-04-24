<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Enums\CRM\AI\TranscriptionStatus;
use App\Models\CRM\CallLog;
use App\Support\TranscriptRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AI-007 — Claude API-powered post-call transcript summarisation service
final class CallTranscriptionService
{
    private const MODEL     = 'claude-sonnet-4-6';
    private const TIMEOUT   = 20;
    private const MAX_CHARS = 32000; // ~8 000 tokens at avg 4 chars/token

    private const VALID_TEMPERATURES = ['Hot', 'Warm', 'Cold'];

    public function __construct(
        private readonly AiUsageLoggingService $usageLogger,
    ) {}

    /**
     * Transcribe and summarise a call log via Claude API.
     * Persists the summary and status on the CallLog record.
     *
     * @return array{interests: list<string>, objections: list<string>, next_steps: list<string>, lead_temperature: string, summary_sentence: string}
     */
    public function transcribe(CallLog $callLog): array
    {
        $transcript = (string) $callLog->transcript_text;
        $truncated  = $this->truncateTranscript($transcript);

        try {
            $raw    = $this->callClaudeApi($truncated, $callLog);
            $parsed = $this->parseResponse($raw);

            $inputTokens  = $raw['usage']['input_tokens'] ?? 0;
            $outputTokens = $raw['usage']['output_tokens'] ?? 0;
            $totalTokens  = (int) $inputTokens + (int) $outputTokens;

            $callLog->update([
                'transcription_summary'     => $parsed,
                'transcription_status'      => TranscriptionStatus::Completed,
                'transcription_model'       => self::MODEL,
                'transcription_token_count' => $totalTokens,
                'transcribed_at'            => now(),
            ]);

            $this->logUsage($callLog, $truncated, $totalTokens, TranscriptionStatus::Completed);

            return $parsed;
        } catch (ConnectionException $e) {
            Log::warning('CallTranscriptionService: Claude API connection timeout', [
                'call_log_uuid' => $callLog->uuid,
                'error'         => $e->getMessage(),
            ]);

            $callLog->update(['transcription_status' => TranscriptionStatus::Failed]);
            $this->logUsage($callLog, $truncated, 0, TranscriptionStatus::Failed);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('CallTranscriptionService: error during transcription', [
                'call_log_uuid' => $callLog->uuid,
                'error'         => $e->getMessage(),
            ]);

            $callLog->update(['transcription_status' => TranscriptionStatus::Failed]);
            $this->logUsage($callLog, $truncated, 0, TranscriptionStatus::Failed);

            throw $e;
        }
    }

    public function buildPrompt(string $transcript): string
    {
        return $transcript;
    }

    /**
     * @return array{interests: list<string>, objections: list<string>, next_steps: list<string>, lead_temperature: string, summary_sentence: string}
     */
    public function parseResponse(array $rawResponse): array
    {
        $text = $rawResponse['content'][0]['text'] ?? '';

        // Strip potential markdown code fences
        $text = preg_replace('/```(?:json)?\s*([\s\S]*?)```/', '$1', $text) ?? $text;

        try {
            $decoded = json_decode(trim($text), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \RuntimeException(
                'Claude API returned non-JSON response: '.substr($text, 0, 200)
            );
        }

        if (! $this->validateStructure($decoded)) {
            throw new \RuntimeException(
                'Claude API response missing required keys or invalid lead_temperature: '.substr($text, 0, 200)
            );
        }

        return [
            'interests'        => array_values((array) $decoded['interests']),
            'objections'       => array_values((array) $decoded['objections']),
            'next_steps'       => array_values((array) $decoded['next_steps']),
            'lead_temperature' => (string) $decoded['lead_temperature'],
            'summary_sentence' => (string) $decoded['summary_sentence'],
        ];
    }

    public function validateStructure(mixed $data): bool
    {
        if (! is_array($data)) {
            return false;
        }

        foreach (['interests', 'objections', 'next_steps', 'lead_temperature', 'summary_sentence'] as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        if (! is_array($data['interests']) || ! is_array($data['objections']) || ! is_array($data['next_steps'])) {
            return false;
        }

        if (! in_array($data['lead_temperature'], self::VALID_TEMPERATURES, true)) {
            return false;
        }

        if (! is_string($data['summary_sentence']) || $data['summary_sentence'] === '') {
            return false;
        }

        return true;
    }

    private function truncateTranscript(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_CHARS) {
            return $text;
        }

        $truncated = mb_substr($text, 0, self::MAX_CHARS);

        return '[NOTE: Transcript was truncated to fit AI context limits. Earlier portion omitted.]'."\n\n".$truncated;
    }

    /** @return array<string, mixed> */
    private function callClaudeApi(string $transcript, CallLog $callLog): array
    {
        $apiKey = (string) config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => self::MODEL,
                'max_tokens' => 1024,
                'system'     => $this->systemPrompt(),
                'messages'   => [
                    ['role' => 'user', 'content' => $transcript],
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Claude API HTTP error '.$response->status().': '.$response->body()
            );
        }

        return $response->json();
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an admissions counselling assistant. Analyse the following call transcript and return a JSON object with these exact keys: interests (array of strings), objections (array of strings), next_steps (array of strings), lead_temperature (one of: Hot, Warm, Cold), summary_sentence (one sentence). Return only valid JSON, no markdown.

Rules:
- interests: list of programme or subject areas the student expressed interest in
- objections: list of concerns, hesitations, or blockers mentioned by the student
- next_steps: list of agreed follow-up actions (concrete, actionable)
- lead_temperature: Hot = very likely to enrol, Warm = interested but undecided, Cold = unlikely or disengaged
- summary_sentence: a single sentence summarising the overall call outcome
- Return only valid JSON with exactly these five keys. No additional text outside the JSON object.
PROMPT;
    }

    private function logUsage(CallLog $callLog, string $transcript, int $tokenCount, TranscriptionStatus $status): void
    {
        $this->usageLogger->log(
            institutionId: $callLog->institution_id,
            campusId: $callLog->campus_id,
            leadId: $callLog->lead_id,
            actorId: null,
            featureKey: 'call_transcription',
            action: 'claude_api_call',
            eventName: 'transcription_'.strtolower($status->value),
            referenceUuid: $callLog->uuid,
            context: [
                'model'       => self::MODEL,
                'tokens'      => $tokenCount,
                'status'      => $status->value,
                'payload'     => TranscriptRedactor::redact($transcript),
            ],
        );
    }
}
