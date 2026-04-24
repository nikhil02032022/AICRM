<?php

declare(strict_types=1);

// BRD: CRM-AI-001 — Unit tests for ConversionPredictionService

use App\Enums\CRM\AI\PredictionStatus;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\AiUsageLoggingService;
use App\Services\CRM\AI\ConversionPredictionService;
use App\Services\CRM\AI\LeadSignalAggregatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();

    $this->lead = Lead::factory()->create([
        'institution_id' => $this->institution->id,
        'lead_score'     => 65,
    ]);

    $this->aggregator  = app(LeadSignalAggregatorService::class);
    $this->usageLogger = app(AiUsageLoggingService::class);
    $this->service     = new ConversionPredictionService($this->aggregator, $this->usageLogger);
});

it('builds a pii_free prompt from signals', function (): void {
    $signals = [
        'source_quality_score'     => 0.85,
        'days_since_enquiry'       => 3,
        'inbound_message_count'    => 4,
        'lead_score'               => 65,
        'questionnaire_completed'  => true,
        'consent_given'            => true,
    ];

    $prompt = $this->service->buildPrompt($signals);

    // Must be valid JSON
    $decoded = json_decode($prompt, true);
    expect($decoded)->toBeArray();

    // Must not contain PII keys
    $piiKeys = ['name', 'first_name', 'last_name', 'email', 'mobile', 'phone'];
    foreach ($piiKeys as $key) {
        expect(array_key_exists($key, $decoded))->toBeFalse("PII key '{$key}' found in prompt");
    }
});

it('parses a valid claude response correctly', function (): void {
    $raw = [
        'content' => [
            ['text' => json_encode([
                'conversion_probability' => 0.78,
                'confidence_score'       => 0.82,
                'prediction_factors'     => [
                    ['factor' => 'Multiple sessions', 'weight' => 'positive', 'impact' => 'high'],
                    ['factor' => 'Payment attempted',  'weight' => 'positive', 'impact' => 'high'],
                    ['factor' => 'Low engagement',     'weight' => 'negative', 'impact' => 'low'],
                ],
            ])],
        ],
        'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
    ];

    $parsed = $this->service->parseResponse($raw);

    expect($parsed['conversion_probability'])->toBe(0.78);
    expect($parsed['confidence_score'])->toBe(0.82);
    expect($parsed['prediction_factors'])->toHaveCount(3);
});

it('handles malformed json response gracefully', function (): void {
    $raw = [
        'content' => [['text' => 'Not valid JSON at all!']],
        'usage'   => ['input_tokens' => 10, 'output_tokens' => 5],
    ];

    expect(fn () => $this->service->parseResponse($raw))->toThrow(\RuntimeException::class);
});

it('sets failed status on api connection timeout', function (): void {
    Http::fake(fn () => throw new ConnectionException('Timeout'));

    $score = $this->service->predict($this->lead);

    expect($score)->toBeInstanceOf(AiLeadScore::class);
    expect($score->prediction_status)->toBe(PredictionStatus::Failed);
    expect($score->conversion_probability)->toBeNull();
});

it('suppresses badge factors when confidence is below threshold', function (): void {
    $raw = [
        'content' => [
            ['text' => json_encode([
                'conversion_probability' => 0.55,
                'confidence_score'       => 0.20, // below 0.30 threshold
                'prediction_factors'     => [
                    ['factor' => 'Some factor', 'weight' => 'positive', 'impact' => 'medium'],
                ],
            ])],
        ],
        'usage' => ['input_tokens' => 80, 'output_tokens' => 40],
    ];

    $parsed = $this->service->parseResponse($raw);

    expect($parsed['prediction_factors'])->toHaveCount(1);
    expect($parsed['prediction_factors'][0]['factor'])->toBe('Insufficient data');
});

it('persists prediction with correct columns', function (): void {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => json_encode([
                    'conversion_probability' => 0.72,
                    'confidence_score'       => 0.88,
                    'prediction_factors'     => [
                        ['factor' => 'Factor A', 'weight' => 'positive', 'impact' => 'high'],
                        ['factor' => 'Factor B', 'weight' => 'negative', 'impact' => 'low'],
                        ['factor' => 'Factor C', 'weight' => 'neutral',  'impact' => 'medium'],
                    ],
                ])],
            ],
            'usage' => ['input_tokens' => 120, 'output_tokens' => 60],
        ]),
    ]);

    $score = $this->service->predict($this->lead);

    expect($score->conversion_probability)->toBe('0.7200');
    expect($score->confidence_score)->toBe('0.8800');
    expect($score->prediction_status)->toBe(PredictionStatus::Completed);
    expect($score->prediction_factors)->toHaveCount(3);
    expect($score->prediction_refreshed_at)->not->toBeNull();
});

it('logs api call via usage logging service', function (): void {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => json_encode([
                    'conversion_probability' => 0.60,
                    'confidence_score'       => 0.75,
                    'prediction_factors'     => [
                        ['factor' => 'F1', 'weight' => 'positive', 'impact' => 'high'],
                        ['factor' => 'F2', 'weight' => 'positive', 'impact' => 'medium'],
                        ['factor' => 'F3', 'weight' => 'negative', 'impact' => 'low'],
                    ],
                ])],
            ],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 55],
        ]),
    ]);

    $this->service->predict($this->lead);

    $this->assertDatabaseHas('ai_usage_logs', [
        'lead_id'     => $this->lead->id,
        'feature_key' => 'conversion_prediction',
        'action'      => 'claude_api_call',
    ]);
});

it('never includes pii fields in prompt', function (): void {
    $signals = $this->service->aggregateSignals($this->lead);

    $piiKeys = ['name', 'first_name', 'last_name', 'email', 'mobile', 'phone', 'address'];
    foreach ($piiKeys as $key) {
        expect(array_key_exists($key, $signals))->toBeFalse("PII key '{$key}' in signals");
    }

    $prompt = $this->service->buildPrompt($signals);
    foreach ($piiKeys as $key) {
        expect(str_contains($prompt, '"'.$key.'"'))->toBeFalse("PII key '{$key}' in prompt string");
    }
});
