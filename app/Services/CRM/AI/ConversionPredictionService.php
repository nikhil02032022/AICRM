<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Enums\CRM\AI\PredictionStatus;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Lead;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// BRD: CRM-AI-001 — Claude API-powered conversion probability prediction service
class ConversionPredictionService
{
    private const MODEL         = 'claude-sonnet-4-6';
    private const TIMEOUT_SEC   = 15;
    private const LOW_CONFIDENCE = 0.30;

    /** Keys that must never appear in the API context logged to AiUsageLog */
    private const PII_KEYS = ['name', 'first_name', 'last_name', 'email', 'mobile', 'phone', 'address'];

    public function __construct(
        private readonly LeadSignalAggregatorService $aggregator,
        private readonly AiUsageLoggingService $usageLogger,
    ) {}

    public function predict(Lead $lead): AiLeadScore
    {
        $signals     = $this->aggregateSignals($lead);
        $userMessage = $this->buildPrompt($signals);

        try {
            $raw    = $this->callClaudeApi($userMessage);
            $parsed = $this->parseResponse($raw);

            return $this->persistPrediction($lead, $parsed, PredictionStatus::Completed, $raw);
        } catch (ConnectionException $e) {
            Log::warning('ConversionPredictionService: Claude API connection timeout', [
                'lead_uuid' => $lead->uuid,
                'error'     => $e->getMessage(),
            ]);

            return $this->persistPrediction($lead, [], PredictionStatus::Failed, []);
        } catch (\Throwable $e) {
            Log::error('ConversionPredictionService: unexpected error', [
                'lead_uuid' => $lead->uuid,
                'error'     => $e->getMessage(),
            ]);

            return $this->persistPrediction($lead, [], PredictionStatus::Failed, []);
        }
    }

    /** @return array<string, mixed> */
    public function aggregateSignals(Lead $lead): array
    {
        return $this->aggregator->aggregate($lead);
    }

    public function buildPrompt(array $signals): string
    {
        return json_encode($signals, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string, mixed> $rawResponse
     * @return array{conversion_probability: float, confidence_score: float, prediction_factors: list<array<string,string>>}
     */
    public function parseResponse(array $rawResponse): array
    {
        $text = $rawResponse['content'][0]['text'] ?? '';

        // Strip potential markdown code fences
        $text = preg_replace('/```(?:json)?\s*([\s\S]*?)```/', '$1', $text) ?? $text;

        try {
            $decoded = json_decode(trim($text), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \RuntimeException('Claude API returned non-JSON response: '.substr($text, 0, 200));
        }

        $prob       = (float) ($decoded['conversion_probability'] ?? 0.0);
        $confidence = (float) ($decoded['confidence_score'] ?? 0.0);
        $factors    = (array)  ($decoded['prediction_factors'] ?? []);

        // Clamp to valid range
        $prob       = max(0.0, min(1.0, $prob));
        $confidence = max(0.0, min(1.0, $confidence));

        if ($confidence < self::LOW_CONFIDENCE) {
            $factors = [
                ['factor' => 'Insufficient data', 'weight' => 'neutral', 'impact' => 'low'],
            ];
        }

        return [
            'conversion_probability' => $prob,
            'confidence_score'       => $confidence,
            'prediction_factors'     => array_slice($factors, 0, 3),
        ];
    }

    /** @return array<string, mixed> */
    private function callClaudeApi(string $userMessage): array
    {
        $apiKey = (string) config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));

        $response = Http::timeout(self::TIMEOUT_SEC)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => self::MODEL,
                'max_tokens' => 512,
                'system'     => $this->systemPrompt(),
                'messages'   => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Claude API HTTP error '.$response->status().': '.$response->body());
        }

        return $response->json();
    }

    private function persistPrediction(Lead $lead, array $parsed, PredictionStatus $status, array $rawResponse): AiLeadScore
    {
        $probability = $parsed['conversion_probability'] ?? null;
        $confidence  = $parsed['confidence_score'] ?? null;
        $factors     = $parsed['prediction_factors'] ?? null;

        $score = AiLeadScore::withoutGlobalScopes()->create([
            'uuid'                    => (string) Str::uuid(),
            'institution_id'          => $lead->institution_id,
            'campus_id'               => $lead->campus_id,
            'lead_id'                 => $lead->id,
            'score'                   => (int) $lead->lead_score,
            'explanation'             => $status === PredictionStatus::Completed
                                            ? 'Claude API conversion probability prediction.'
                                            : 'Prediction failed — see ai_usage_logs for details.',
            'model_version'           => self::MODEL,
            'metadata'                => ['source' => 'claude_api'],
            'calculated_at'           => now(),
            'conversion_probability'  => $probability,
            'confidence_score'        => $confidence,
            'prediction_factors'      => $factors,
            'prediction_refreshed_at' => now(),
            'prediction_status'       => $status,
        ]);

        $inputTokens  = $rawResponse['usage']['input_tokens'] ?? 0;
        $outputTokens = $rawResponse['usage']['output_tokens'] ?? 0;

        $this->usageLogger->log(
            institutionId: $lead->institution_id,
            campusId: $lead->campus_id,
            leadId: $lead->id,
            actorId: null,
            featureKey: 'conversion_prediction',
            action: 'claude_api_call',
            eventName: 'prediction_'.strtolower($status->value),
            referenceUuid: $score->uuid,
            context: $this->scrubPii([
                'model'         => self::MODEL,
                'tokens_input'  => $inputTokens,
                'tokens_output' => $outputTokens,
                'status'        => $status->value,
                'prediction_uuid' => $score->uuid,
            ]),
        );

        return $score;
    }

    /** @param array<string, mixed> $context */
    private function scrubPii(array $context): array
    {
        return array_filter(
            $context,
            static fn (string $key) => ! in_array(strtolower($key), self::PII_KEYS, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a lead conversion probability analyser for an educational CRM. You receive behavioural signals about a prospective student enquiry and predict the likelihood of that enquiry converting to an enrolled student.

Respond with ONLY valid JSON in this exact format — no explanations, no markdown, no text outside the JSON:
{
  "conversion_probability": 0.72,
  "confidence_score": 0.85,
  "prediction_factors": [
    {"factor": "Multiple counselling sessions completed", "weight": "positive", "impact": "high"},
    {"factor": "High inbound message frequency", "weight": "positive", "impact": "medium"},
    {"factor": "Document submission incomplete", "weight": "negative", "impact": "low"}
  ]
}

Rules:
- conversion_probability: float 0.0 to 1.0 (probability of enrolling)
- confidence_score: float 0.0 to 1.0 (your confidence in the prediction given available data)
- prediction_factors: exactly 3 items, each with "factor" (string), "weight" ("positive", "negative", or "neutral"), "impact" ("high", "medium", or "low")
- If data is sparse, return a low confidence_score (< 0.30) rather than guessing
- Never infer or include any personally identifiable information
PROMPT;
    }
}
